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

func getHJTs(c *gin.Context) {
	hjts := make([]HJT, 0)
	err := Database.Find(&hjts).Error
	if err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}
	c.JSON(http.StatusOK, hjts)
}

func getHJT(c *gin.Context) {
	name := c.Param("name")
	if strings.HasPrefix(name, "/") {
		name = strings.TrimPrefix(name, "/")
	}
	if _, exists := c.GetQuery("search"); exists {
		hjts := make([]HJT, 0)
		err := Database.Where("name LIKE ?", name+"%").Find(&hjts).Error
		if err != nil {
			c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
			return
		}
		c.JSON(http.StatusOK, hjts)
	} else {
		hjt := HJT{Name: name}
		err := Database.Where(&hjt).First(&hjt).Error
		if err != nil {
			if err == gorm.ErrRecordNotFound {
				c.Status(404)
			} else {
				c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
			}
			return
		}
		c.JSON(http.StatusOK, hjt)
	}
}

func updateHJT(c *gin.Context) {
	name := c.Param("name")
	if strings.HasPrefix(name, "/") {
		name = strings.TrimPrefix(name, "/")
	}

	body, err := ioutil.ReadAll(io.LimitReader(c.Request.Body, MaxHJTLength))
	if err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	hjt := HJT{Name: name, Content: string(body)}
	err = Database.Clauses(clause.OnConflict{UpdateAll: true}).Create(&hjt).Error
	if err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}
	c.JSON(http.StatusOK, hjt)
}

func deleteHJT(c *gin.Context) {
	name := c.Param("name")
	if strings.HasPrefix(name, "/") {
		name = strings.TrimPrefix(name, "/")
	}

	hjt := HJT{Name: name}
	res := Database.Where(&hjt).Delete(&hjt)
	if res.Error != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: res.Error.Error()})
		return
	} else if res.RowsAffected == 0 {
		c.Status(http.StatusNotFound)
	} else {
		c.Status(http.StatusNoContent)
	}
}
