package main

import (
	"fmt"
	"github.com/MinecraftHopper/panel/env"
	"github.com/spf13/viper"
	"gorm.io/driver/mysql"
	"gorm.io/gorm"
)

var Database *gorm.DB

func ConnectDatabase() error {
	var err error

	viper.SetDefault("db.username", "panel")
	viper.SetDefault("db.password", "panel")
	viper.SetDefault("db.host", "127.0.0.1:3306")
	viper.SetDefault("db.database", "panel")

	var connString = fmt.Sprintf("%s:%s@tcp(%s)/%s?charset=utf8mb4&parseTime=True&loc=Local",
		env.Get("db.username"),
		env.Get("db.password"),
		env.Get("db.host"),
		env.Get("db.database"),
	)

	Database, err = gorm.Open(mysql.New(mysql.Config{
		DSN: connString,
	}), &gorm.Config{})

	if Database != nil {
		Database = Database.Debug()
	}

	return err
}
