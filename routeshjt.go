package main

import (
	"encoding/json"
	"github.com/gin-gonic/gin"
	"gorm.io/gorm"
	"net/http"
	"strconv"
)

func getHJTs(c *gin.Context) {
	records := make([]HJT, 0)
	err := Database.Find(&records).Error
	if err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}
	c.JSON(http.StatusOK, records)
}

func getHJT(c *gin.Context) {
	param := c.Param("id")
	var tempId uint64
	var err error

	if tempId, err = strconv.ParseUint(param, 10, 32); err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	id := uint(tempId)

	record := HJT{ID: id}
	err = Database.Where(&record).First(&record).Error
	if err != nil {
		if err == gorm.ErrRecordNotFound {
			c.Status(404)
		} else {
			c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		}
		return
	}
	c.JSON(http.StatusOK, record)
}

func updateHJT(c *gin.Context) {
	param := c.Param("id")
	var tempId uint64
	var err error

	//if it's a POST and there isn't an id, it's okay, otherwise it's a PUT with an id
	if c.Request.Method == http.MethodPost && param == "" {
	} else if tempId, err = strconv.ParseUint(param, 10, 32); err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	var record HJT
	err = json.NewDecoder(c.Request.Body).Decode(&record)
	if err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	if tempId != 0 {
		record.ID = uint(tempId)
	}

	if record.SeverityDescription != "" {
		record.Severity = SeverityFromString(record.SeverityDescription)
	} else {
		record.SeverityDescription = record.Severity.ToString()
	}

	if record.MatchCriteria == "" {
		record.MatchCriteria = record.Name
	}

	err = Database.Save(&record).Error

	if err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}
	c.JSON(http.StatusOK, record)
}

func deleteHJT(c *gin.Context) {
	param := c.Param("id")
	var tempId uint64
	var err error

	//if it's a POST and there isn't an id, it's okay, otherwise it's a PUT with an id
	if c.Request.Method == http.MethodPost && param == "" {
	} else if tempId, err = strconv.ParseUint(param, 10, 32); err != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: err.Error()})
		return
	}

	record := HJT{ID: uint(tempId)}
	res := Database.Delete(&record)
	if res.Error != nil {
		c.JSON(http.StatusInternalServerError, Error{Message: res.Error.Error()})
		return
	} else if res.RowsAffected == 0 {
		c.Status(http.StatusNotFound)
	} else {
		c.Status(http.StatusNoContent)
	}
}
