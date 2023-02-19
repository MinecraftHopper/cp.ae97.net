package env

import (
	"github.com/spf13/viper"
	"io"
	"log"
	"os"
	"strings"
)

var cache = make(map[string]string)

func init() {
	viper.AutomaticEnv()
	viper.SetEnvKeyReplacer(strings.NewReplacer(".", "_"))
}

func Get(key string) string {
	val, exists := cache[key]
	if exists {
		return val
	}

	filename := viper.GetString(key + ".file")
	if filename == "" {
		return viper.GetString(key)
	}
	val, err := readSecret(filename)
	if err != nil {
		log.Printf("error reading secret: %s", err.Error())
	}
	//update cache with the full value, so we don't constantly read it
	cache[key] = val
	return val
}

func readSecret(file string) (string, error) {
	f, err := os.Open(file)
	if err != nil {
		return "", err
	}
	defer f.Close()

	data, err := io.ReadAll(f)
	if err != nil {
		return "", err
	}

	return strings.TrimSpace(string(data)), nil
}
