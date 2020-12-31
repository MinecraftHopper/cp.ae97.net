package main

import (
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"
	"gorm.io/gorm/clause"
	"io"
	"io/ioutil"
	"net/http"
	"strings"
)

func getFactoids(c *gin.Context) {
	factoids := make([]Factoid, 0)
	err := Database.Find(&factoids).Error
	if err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}
	c.JSON(http.StatusOK, factoids)
}

func getFactoid(c *gin.Context) {
	name := c.Param("name")
	if strings.HasPrefix(name, "/") {
		name = strings.TrimPrefix(name, "/")
	}
	if _, exists := c.GetQuery("search"); exists {
		factoids := make([]Factoid, 0)
		err := Database.Where("name LIKE ?", name + "%").Find(&factoids).Error
		if err != nil {
			c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
			return
		}
		c.JSON(http.StatusOK, factoids)
	} else {
		factoid := Factoid{Name: name}
		err := Database.Where(&factoid).First(&factoid).Error
		if err != nil {
			if err == gorm.ErrRecordNotFound {
				c.Status(404)
			} else {
				c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
			}
			return
		}
		c.JSON(http.StatusOK, factoid)
	}
}

func updateFactoid(c *gin.Context) {
	name := c.Param("name")
	if strings.HasPrefix(name, "/") {
		name = strings.TrimPrefix(name, "/")
	}

	body, err := ioutil.ReadAll(io.LimitReader(c.Request.Body, MaxFactoidLength))
	if err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	factoid := Factoid{Name: name, Content: string(body)}
	err = Database.Clauses(clause.OnConflict{ UpdateAll: true }).Create(&factoid).Error
	if err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}
	c.JSON(http.StatusOK, factoid)
}

func deleteFactoid(c *gin.Context) {
	name := c.Param("name")
	if strings.HasPrefix(name, "/") {
		name = strings.TrimPrefix(name, "/")
	}

	factoid := Factoid{Name: name}
	res := Database.Where(&factoid).Delete(&factoid)
	if res.Error != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: res.Error.Error()})
		return
	} else if res.RowsAffected == 0 {
		c.Status(http.StatusNotFound)
	} else {
		c.Status(http.StatusNoContent)
	}
}