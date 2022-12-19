<?php

namespace Wallabag\ApiBundle\Controller;

use Craue\ConfigBundle\Util\Config;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Event\UserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Operation;
use Swagger\Annotations as SWG;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Wallabag\ApiBundle\Entity\Client;
use Wallabag\UserBundle\Entity\User;
use Wallabag\UserBundle\Form\NewUserType;

class UserRestController extends WallabagRestController
{
    /**
     * Retrieve current logged in user informations.
     *
     * @Operation(
     *     tags={"User"},
     *     summary="Retrieve current logged in user informations.",
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful"
     *     )
     * )
     *
     * @Route("/api/user.{_format}", methods={"GET"}, name="api_get_user", defaults={"_format": "json"})
     *
     * @return JsonResponse
     */
    public function getUserAction()
    {
        $this->validateAuthentication();

        return $this->sendUser($this->getUser());
    }

    /**
     * Register an user and create a client.
     *
     * @Operation(
     *     tags={"User"},
     *     summary="Register an user and create a client.",
     *     @SWG\Parameter(
     *         name="username",
     *         in="body",
     *         description="The user's username",
     *         required=true,
     *         @SWG\Schema(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="password",
     *         in="body",
     *         description="The user's password",
     *         required=true,
     *         @SWG\Schema(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="email",
     *         in="body",
     *         description="The user's email",
     *         required=true,
     *         @SWG\Schema(type="string")
     *     ),
     *     @SWG\Parameter(
     *         name="client_name",
     *         in="body",
     *         description="The client name (to be used by your app)",
     *         required=true,
     *         @SWG\Schema(type="string")
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="Returned when successful"
     *     )
     * )
     *
     * @todo Make this method (or the whole API) accessible only through https
     *
     * @Route("/api/user.{_format}", methods={"PUT"}, name="api_put_user", defaults={"_format": "json"})
     *
     * @return JsonResponse
     */
    public function putUserAction(Request $request, Config $craueConfig, UserManagerInterface $userManager, EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        if (!$this->getParameter('fosuser_registration') || !$craueConfig->get('api_user_registration')) {
            $json = $this->serializer->serialize(['error' => "Server doesn't allow registrations"], 'json');

            return (new JsonResponse())
                ->setJson($json)
                ->setStatusCode(JsonResponse::HTTP_FORBIDDEN);
        }

        $user = $userManager->createUser();
        \assert($user instanceof User);
        // user will be disabled BY DEFAULT to avoid spamming account to be enabled
        $user->setEnabled(false);

        $form = $this->createForm(NewUserType::class, $user, [
            'csrf_protection' => false,
        ]);

        // simulate form submission
        $form->submit([
            'username' => $request->request->get('username'),
            'plainPassword' => [
                'first' => $request->request->get('password'),
                'second' => $request->request->get('password'),
            ],
            'email' => $request->request->get('email'),
        ]);

        if ($form->isSubmitted() && false === $form->isValid()) {
            $view = $this->view($form, 400);
            $view->setFormat('json');

            // handle errors in a more beautiful way than the default view
            $data = json_decode($this->handleView($view)->getContent(), true)['errors']['children'];
            $errors = [];

            if (isset($data['username']['errors'])) {
                $errors['username'] = $this->translateErrors($data['username']['errors']);
            }

            if (isset($data['email']['errors'])) {
                $errors['email'] = $this->translateErrors($data['email']['errors']);
            }

            if (isset($data['plainPassword']['children']['first']['errors'])) {
                $errors['password'] = $this->translateErrors($data['plainPassword']['children']['first']['errors']);
            }

            $json = $this->serializer->serialize(['error' => $errors], 'json');

            return (new JsonResponse())
                ->setJson($json)
                ->setStatusCode(JsonResponse::HTTP_BAD_REQUEST);
        }

        // create a default client
        $client = new Client($user);
        $client->setName($request->request->get('client_name', 'Default client'));

        $entityManager->persist($client);

        $user->addClient($client);

        $userManager->updateUser($user);

        // dispatch a created event so the associated config will be created
        $eventDispatcher->dispatch(new UserEvent($user, $request), FOSUserEvents::USER_CREATED);

        return $this->sendUser($user, 'user_api_with_client', JsonResponse::HTTP_CREATED);
    }

    /**
     * Send user response.
     *
     * @param string $group  Used to define with serialized group might be used
     * @param int    $status HTTP Status code to send
     *
     * @return JsonResponse
     */
    private function sendUser(User $user, $group = 'user_api', $status = JsonResponse::HTTP_OK)
    {
        $json = $this->serializer->serialize(
            $user,
            'json',
            SerializationContext::create()->setGroups([$group])
        );

        return (new JsonResponse())
            ->setJson($json)
            ->setStatusCode($status);
    }

    /**
     * Translate errors message.
     *
     * @param array $errors
     *
     * @return array
     */
    private function translateErrors($errors)
    {
        $translatedErrors = [];
        foreach ($errors as $error) {
            $translatedErrors[] = $this->translator->trans($error);
        }

        return $translatedErrors;
    }
}
