<?php

namespace Wallabag\CoreBundle\Controller\Import;

use Craue\ConfigBundle\Util\Config;
use OldSound\RabbitMqBundle\RabbitMq\Producer as RabbitMqProducer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wallabag\CoreBundle\Import\ElcuratorImport;
use Wallabag\CoreBundle\Redis\Producer as RedisProducer;

class ElcuratorController extends WallabagController
{
    private ElcuratorImport $elcuratorImport;
    private Config $craueConfig;
    private RabbitMqProducer $rabbitMqProducer;
    private RedisProducer $redisProducer;

    public function __construct(ElcuratorImport $elcuratorImport, Config $craueConfig, RabbitMqProducer $rabbitMqProducer, RedisProducer $redisProducer)
    {
        $this->elcuratorImport = $elcuratorImport;
        $this->craueConfig = $craueConfig;
        $this->rabbitMqProducer = $rabbitMqProducer;
        $this->redisProducer = $redisProducer;
    }

    /**
     * @Route("/import/elcurator", name="import_elcurator")
     */
    public function indexAction(Request $request, TranslatorInterface $translator)
    {
        return parent::indexAction($request, $translator);
    }

    protected function getImportService()
    {
        if ($this->craueConfig->get('import_with_rabbitmq')) {
            $this->elcuratorImport->setProducer($this->rabbitMqProducer);
        } elseif ($this->craueConfig->get('import_with_redis')) {
            $this->elcuratorImport->setProducer($this->redisProducer);
        }

        return $this->elcuratorImport;
    }

    protected function getImportTemplate()
    {
        return 'Import/Elcurator/index.html.twig';
    }
}
