package main

import (
	"github.com/MinecraftHopper/panel/env"
	"github.com/spf13/viper"
	"net/http"
)

const MaxFactoidLength = 1000
const DiscordEndpoint = "https://discord.com/api/v6/oauth2/token"

var HttpClient = http.Client{}

func main() {
	viper.AutomaticEnv()
	_ = viper.ReadInConfig()

	_ = ConnectDatabase()

	engine := ConfigureRoutes()

	viper.SetDefault("bind", "0.0.0.0:8080")
	err := engine.Run(env.Get("bind"))
	if err != nil {
		panic(err)
	}
}
