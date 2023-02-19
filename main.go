package main

import (
	"github.com/MinecraftHopper/panel/env"
	"github.com/spf13/viper"
	"net/http"
)

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
