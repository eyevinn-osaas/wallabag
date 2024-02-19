FROM node:20-alpine
RUN apk add python3 make g++
ENV NODE_ENV=production
ENV PORT=8080
RUN mkdir /app
RUN chown node:node /app
WORKDIR /app
USER node
COPY --chown=node:node ["./", "./"]
RUN NODE_ENV="" npm ci || npm install --include=dev --production=false
CMD ["node", "./app/Resources/static/themes/_global/index.js"]