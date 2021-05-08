package main

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"
	"net/http"
)

var Permissions = map[string]string{
	"factoid.manage": "Manage factoids",
	"user.manage": "Manage users",
	"hjt.manage": "Manage HJT",
	"logs.view": "View logs",
}

func getFlags(c *gin.Context) {
	c.JSON(http.StatusOK, Permissions)
}

func setUserFlags(c *gin.Context) {
	userId := c.Param("user")

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
	c.JSON(http.StatusOK, perms)
}
