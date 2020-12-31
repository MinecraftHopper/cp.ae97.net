package main

import (
	"github.com/gin-contrib/sessions"
	"github.com/gin-contrib/sessions/cookie"
	"github.com/gin-gonic/gin"
	"github.com/spf13/viper"
	"net/http"
)

func ConfigureRoutes() *gin.Engine {
	engine := gin.Default()

	viper.SetDefault("session.secret", "changeme")
	viper.SetDefault("session.name", "panelsession")

	store := cookie.NewStore([]byte(viper.GetString("session.secret")))
	engine.Use(sessions.Sessions(viper.GetString("session.name"), store))

	engine.Handle("GET", "/api/factoid", getFactoids)
	engine.Handle("GET", "/api/factoid/*name", getFactoid)
	engine.Handle("PUT", "/api/factoid/*name", authorized("factoid.manage"), updateFactoid)
	engine.Handle("DELETE", "/api/factoid/*name", authorized("factoid.manage"), deleteFactoid)

	engine.Handle("GET", "/login", login)
	engine.Handle("GET", "/login-callback", loginCallback)

	return engine
}

func authorized(perm string) gin.HandlerFunc {
	return func(c *gin.Context) {
		session := sessions.Default(c)
		discordId, ok := session.Get("discordId").(string)
		if !ok || discordId == ""{
			c.AbortWithStatus(http.StatusUnauthorized)
			return
		}
		permission := &Permission{
			DiscordId:  discordId,
			Permission: perm,
		}

		exists := int64(0)
		err := Database.Model(permission).Where(permission).Count(&exists).Error
		if err != nil {
			c.AbortWithStatusJSON(http.StatusInternalServerError, Error{Message: err.Error()})
			return
		}
		if exists > 1 {
			c.AbortWithStatus(http.StatusUnauthorized)
			return
		}
		c.Next()
	}
}
