package main

import (
	"encoding/json"
	"errors"
	"github.com/spf13/viper"
	"net/http"
	"net/url"
)

var userInfoEndpoint *url.URL

func init() {
	var err error
	userInfoEndpoint, err = url.Parse("https://discord.com/api/users/@me")
	if err != nil {
		panic(err)
	}
}

func redeemCode(code string) (string, error) {
	clientId := viper.GetString("discord.clientid")
	clientSecret := viper.GetString("discord.clientsecret")
	redirectUrl := viper.GetString("discord.redirecturl")

	data := map[string]string {
		"client_id": clientId,
		"client_secret": clientSecret,
		"grant_type": "authorization_code",
		"code": code,
		"redirect_uri": redirectUrl,
		"scope": "identify email connections",
	}

	values := url.Values{}
	for k, v := range data {
		values.Add(k, v)
	}

	response, err := HttpClient.PostForm(DiscordEndpoint, values)
	if err != nil {
		return "", err
	}
	defer response.Body.Close()

	if response.StatusCode != http.StatusOK {
		return "", errors.New("invalid response code from Discord (" + response.Status + ")")
	}

	discordResponse := DiscordOAuth2Response{}
	err = json.NewDecoder(response.Body).Decode(&discordResponse)
	if err != nil {
		return "", err
	}
	return discordResponse.AccessToken, nil
}

func getUserId(accessToken string) (string, error) {
	request := &http.Request{
		URL: userInfoEndpoint,
		Header: map[string][]string{"Authorization": {"Bearer " + accessToken}},
	}

	response, err := HttpClient.Do(request)
	if err != nil {
		return "", err
	}
	defer response.Body.Close()

	if response.StatusCode != http.StatusOK {
		return "", errors.New("invalid response code from Discord (" + response.Status + ")")
	}

	discordResponse := DiscordUser{}
	err = json.NewDecoder(response.Body).Decode(&discordResponse)
	if err != nil {
		return "", err
	}
	return discordResponse.Id, nil
}