###
# Builder to compile our golang code
###
FROM golang:alpine AS builder

ARG tags=none

ENV CGOENABLED=1

RUN go version && \
    apk add --update --no-cache git curl nodejs npm && \
    mkdir /panel

WORKDIR /build
COPY . .
RUN go build -v -tags $tags -buildvcs=false -o /panel/panel -v github.com/MinecraftHopper/panel && \
    npm install && \
    npm run-script build && \
    mv dist/* /panel

###
# Now generate our smaller image
###
FROM alpine

COPY --from=builder /panel /panel

ENV DISCORD_CLIENTID="" \
    DISCORD_CLIENTSECRET="" \
    DB_USERNAME=panel \
    DB_PASSWORD=panel \
    DB_HOST=127.0.0.1 \
    DB_DATABASE=panel \
    SECRET_NAME=panel \
    SESSION_SECRET=secret \
    WEB_HOST=http://localhost:8080 \
    WEB_ROOT=/panel

WORKDIR /panel

EXPOSE 8080

ENTRYPOINT ["/panel/panel"]
CMD []
