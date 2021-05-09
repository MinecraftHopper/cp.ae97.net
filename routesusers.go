package main

import (
	"github.com/gin-contrib/sessions"
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"
	"net/http"
)

var Permissions = map[string]string{
	"factoid.manage": "Manage factoids",
	"user.manage":    "Manage users",
	"hjt.manage":     "Manage HJT",
	"logs.view":      "View logs",
}

func getFlags(c *gin.Context) {
	c.JSON(http.StatusOK, Permissions)
}

func setUserFlags(c *gin.Context) {
	userId := c.Param("user")

	session := sessions.Default(c)
	discordId, ok := session.Get("discordId").(string)
	if !ok || discordId == "" {
		c.AbortWithStatus(http.StatusUnauthorized)
		return
	}

	if discordId == userId {
		c.JSON(http.StatusForbidden, Error{Message: "cannot edit yourself"})
		return
	}

	perms := make([]string, 0)
	err := c.Bind(&perms)
	if err != nil {
		c.JSON(http.StatusBadRequest, Error{Message: err.Error()})
		return
	}

	for p := range perms {
		if _, ok := Permissions[perms[p]]; !ok {
			c.JSON(http.StatusBadRequest, Error{Message: perms[p] + " is not a valid permission"})
			return
		}
	}

	var transaction = Database.Begin()
	var rollback = true
	defer func() {
		if rollback {
			transaction.Rollback()
		}
	}()

	err = Database.Where(&Permission{DiscordId: userId}).Delete(&Permission{}).Error
	if err != nil && gorm.ErrRecordNotFound != err {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	for p := range perms {
		err = Database.Create(&Permission{DiscordId: userId, Permission: perms[p]}).Error
	}

	if err != nil && gorm.ErrRecordNotFound != err {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	transaction.Commit()
	rollback = false
	c.Status(http.StatusNoContent)
}

func getUserFlags(c *gin.Context) {
	userId := c.Param("user")

	perms := make([]string, 0)
	err := Database.Model(&Permission{}).Where(&Permission{DiscordId: userId}).Select("permission").Find(&perms).Error
	if err != nil && gorm.ErrRecordNotFound != err {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	//we have the perms for this user, but let's also get their discord info so it's something we can show
	user, err := getUser(userId)
	if err != nil {
		if err == NoDiscordUser {
			c.JSON(http.StatusNotFound, Error{Message: err.Error()})
		} else {
			c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		}
		return
	}

	c.JSON(http.StatusOK, map[string]interface{}{
		"user": user,
		"perms": perms,
	})
}
