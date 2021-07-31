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
RUN go build -v -tags $tags -o /panel/panel -v github.com/MinecraftHopper/panel && \
    npm install && \
    npm run-script build && \
    mv dist/* /panel

###
# Now generate our smaller image
###
FROM alpine

COPY --from=builder /panel /panel

ENV DISCORD.CLIENTID=
ENV DISCORD.CLIENTSECRET=
ENV DB.USERNAME=panel
ENV DB.PASSWORD=panel
ENV DB.HOST=127.0.0.1
ENV DB.DATABASE=panel
ENV SECRET.NAME=panel
ENV SESSION.SECRET=secret
ENV WEB.HOST=http://localhost:8080
ENV WEB.ROOT=/panel

WORKDIR /panel

EXPOSE 8080

ENTRYPOINT ["/panel/panel"]
CMD []
