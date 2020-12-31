###
# Builder to compile our golang code
###
FROM golang:alpine AS builder

WORKDIR /build
COPY . .

RUN go build -o panel -v github.com/MinecraftHopper/panel

###
# Now generate our smaller image
###
FROM alpine

COPY --from=builder /build/panel /go/bin/panel

ENV DISCORD.CLIENTID=
ENV DISCORD.CLIENTSECRET=
ENV DISCORD.REDIRECTURL=http://localhost:8080/login-callback
ENV DB.USERNAME=panel
ENV DB.PASSWORD=panel
ENV DB.HOST=127.0.0.1
ENV DB.DATABASE=panel
ENV SECRET.NAME=panel
ENV SESSION.SECRET=secret

EXPOSE 8080

ENTRYPOINT ["/go/bin/panel"]
CMD []