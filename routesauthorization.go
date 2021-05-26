package main

import (
	"fmt"
	"github.com/gin-contrib/sessions"
	"github.com/gin-gonic/gin"
	"github.com/google/uuid"
	"github.com/spf13/viper"
	"gorm.io/gorm"
	"net/http"
	url2 "net/url"
	"strings"
)

func login(c *gin.Context) {
	state := uuid.New().String()

	session := sessions.Default(c)
	session.Set("state", state)
	_ = session.Save()

	url := fmt.Sprintf("https://discord.com/api/oauth2/authorize?response_type=code&client_id=%s&scope=%s&state=%s&redirect_uri=%s",
		viper.GetString("discord.clientid"),
		strings.Join([]string{"identify", "guilds"}, "%20"),
		state,
		url2.QueryEscape(viper.GetString("web.host") + "/login-callback"),
	)

	c.Redirect(http.StatusTemporaryRedirect, url)
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
