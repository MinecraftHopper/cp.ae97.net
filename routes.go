package main

import (
	"github.com/gin-contrib/gzip"
	"github.com/gin-contrib/sessions"
	"github.com/gin-contrib/sessions/cookie"
	"github.com/gin-gonic/gin"
	"github.com/spf13/viper"
	"net/http"
	"os"
	"path/filepath"
	"strings"
)

var noHandle404 = []string{"/api/"}
var webRoot string

func ConfigureRoutes() *gin.Engine {
	e := gin.Default()

	viper.SetDefault("session.secret", "changeme")
	viper.SetDefault("session.name", "panelsession")
	wd, err := os.Getwd()
	if err != nil {
		panic(err)
	}
	viper.SetDefault("web.root", wd)

	webRoot = viper.GetString("web.root")

	store := cookie.NewStore([]byte(viper.GetString("session.secret")))
	e.Use(sessions.Sessions(viper.GetString("session.name"), store))

	e.Handle("GET", "/api/factoid", allowCORS, getFactoids)
	e.Handle("GET", "/api/factoid/*name", allowCORS, getFactoid)
	e.Handle("PUT", "/api/factoid/*name", authorized("factoid.manage"), updateFactoid)
	e.Handle("DELETE", "/api/factoid/*name", authorized("factoid.manage"), deleteFactoid)
	e.Handle("OPTIONS", "/api/factoid", allowCORS, CreateOptions("GET"))
	e.Handle("OPTIONS", "/api/factoid/*name", allowCORS, CreateOptions("GET", "PUT", "DELETE"))

	e.Handle("GET", "/api/hjt", allowCORS, getHJTs)
	e.Handle("GET", "/api/hjt/:id", allowCORS, getHJT)
	e.Handle("PUT", "/api/hjt/:id", /*authorized("hjt.manage"),*/ updateHJT)
	e.Handle("POST", "/api/hjt", /*authorized("hjt.manage"),*/ updateHJT)
	e.Handle("DELETE", "/api/hjt/:id", /*authorized("hjt.manage"),*/ deleteHJT)
	e.Handle("OPTIONS", "/api/hjt", allowCORS, CreateOptions("GET", "POST"))
	e.Handle("OPTIONS", "/api/hjt/:id", allowCORS, CreateOptions("GET", "PUT", "DELETE"))

	e.Handle("GET", "/api/flags", getFlags)
	e.Handle("GET", "/api/flags/:user", authorized("user.manage"), getUserFlags)
	e.Handle("PUT", "/api/flags/:user", authorized("user.manage"), setUserFlags)
	e.Handle("OPTIONS", "/api/flags", CreateOptions("GET"))
	e.Handle("OPTIONS", "/api/flags/:user", CreateOptions("GET", "PUT"))

	e.Handle("GET", "/login", login)
	e.Handle("GET", "/login-callback", loginCallback)

	css := e.Group("/css")
	{
		css.Use(gzip.Gzip(gzip.DefaultCompression))
		css.StaticFS("", http.Dir(filepath.Join(webRoot, "css")))
	}
	fonts := e.Group("/fonts")
	{
		fonts.Use(gzip.Gzip(gzip.DefaultCompression))
		fonts.StaticFS("", http.Dir(filepath.Join(webRoot, "fonts")))
	}
	img := e.Group("/img")
	{
		img.StaticFS("", http.Dir(filepath.Join(webRoot, "img")))
	}
	js := e.Group("/js", setContentType("application/javascript"))
	{
		js.Use(gzip.Gzip(gzip.DefaultCompression))
		js.StaticFS("", http.Dir(filepath.Join(webRoot, "js")))
	}
	e.StaticFile("/favicon.png", filepath.Join(webRoot, "favicon.png"))
	e.StaticFile("/favicon.ico", filepath.Join(webRoot, "favicon.ico"))
	e.NoRoute(handle404)

	return e
}

func CreateOptions(options ...string) gin.HandlerFunc {
	replacement := make([]string, len(options)+1)

	copy(replacement, options)

	replacement[len(options)] = "OPTIONS"
	res := strings.Join(replacement, ",")

	return func(c *gin.Context) {
		c.Header("Access-Control-Allow-Origin", "*")
		c.Header("Access-Control-Allow-Methods", res)
		c.Header("Access-Control-Allow-Headers", "authorization, origin, content-type, accept")
		c.Header("Allow", res)
		c.Header("Content-Type", "application/json")
		c.AbortWithStatus(http.StatusOK)
	}
}

func allowCORS(c *gin.Context) {
	c.Header("Access-Control-Allow-Origin", "*")
}

func authorized(perm string) gin.HandlerFunc {
	return func(c *gin.Context) {
		session := sessions.Default(c)
		discordId, ok := session.Get("discordId").(string)
		if !ok || discordId == "" {
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

		if exists == 0 {
			c.AbortWithStatus(http.StatusUnauthorized)
			return
		}
		c.Next()
	}
}

func handle404(c *gin.Context) {
	for _, v := range noHandle404 {
		if strings.HasPrefix(c.Request.URL.Path, v) {
			c.AbortWithStatus(http.StatusNotFound)
			return
		}
	}

	path := strings.TrimPrefix(c.Request.URL.Path, "/")

	if strings.HasSuffix(path, ".js") {
		c.Header("Content-Type", "application/javascript")
		c.File(filepath.Join(webRoot, path))
		return
	}

	if strings.HasSuffix(path, ".json") {
		c.Header("Content-Type", "application/json")
		c.File(filepath.Join(webRoot, path))
		return
	}

	if strings.HasSuffix(path, ".css") {
		c.Header("Content-Type", "text/css")
		c.File(filepath.Join(webRoot, path))
		return
	}

	if strings.HasSuffix(path, ".tar") {
		c.Header("Content-Type", "application/x-tar")
		c.File(filepath.Join(webRoot, path))
		return
	}

	c.File(filepath.Join(webRoot, "index.html"))
}

func setContentType(contentType string) gin.HandlerFunc {
	return func(c *gin.Context) {
		c.Header("Content-Type", contentType)
		c.Next()
	}
}
