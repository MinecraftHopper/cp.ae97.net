package main

import (
	"fmt"
	"github.com/MinecraftHopper/panel/env"
	"github.com/gin-contrib/sessions"
	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
	"gorm.io/gorm"
	"net/http"
	"net/url"
	"strings"
)

func login(c *gin.Context) {
	state := uuid.New().String()

	session := sessions.Default(c)
	session.Set("state", state)
	_ = session.Save()

	u := fmt.Sprintf("https://discord.com/api/oauth2/authorize?response_type=code&client_id=%s&scope=%s&state=%s&redirect_uri=%s",
		env.Get("discord.clientid"),
		strings.Join([]string{"identify", "guilds"}, "%20"),
		state,
		url.QueryEscape(env.Get("web.host")+"/login-callback"),
	)

	c.Redirect(http.StatusTemporaryRedirect, u)
}

func loginCallback(c *gin.Context) {
	code := c.Query("code")
	state := c.Query("state")

	session := sessions.Default(c)
	expectedState, ok := session.Get("state").(string)
	session.Delete("state")
	if !ok || expectedState != state {
		c.JSON(http.StatusBadRequest, Error{Message: "missing or invalid state"})
		return
	}

	accessToken, err := redeemCode(code)
	if err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	userId, err := getUserId(accessToken)
	if err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	session.Set("discordId", userId)

	//for UI help, we'll save off the perms to the client
	perms := make([]string, 0)
	err = Database.Model(&Permission{}).Where(&Permission{DiscordId: userId}).Select("permission").Find(&perms).Error
	if err != nil && gorm.ErrRecordNotFound != err {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	//perms don't really matter too much in terms of security, so we'll not enforce it being secure
	c.SetCookie("perms", strings.Join(perms, "+"), 64000, "/", "", true, false)

	_ = session.Save()
	c.Redirect(http.StatusTemporaryRedirect, "/")
}

func logout(c *gin.Context) {
	sessions.Default(c).Clear()
}
