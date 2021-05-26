package main

import (
	"encoding/json"
	"errors"
	"github.com/spf13/viper"
	"net/http"
	"net/url"
)

const userInfoEndpoint string = "https://discord.com/api/users/"
var NoDiscordUser = errors.New("no discord user")

func redeemCode(code string) (string, error) {
	clientId := viper.GetString("discord.clientid")
	clientSecret := viper.GetString("discord.clientsecret")
	redirectUrl := viper.GetString("web.host") + "/login-callback"

	data := map[string]string{
		"client_id":     clientId,
		"client_secret": clientSecret,
		"grant_type":    "authorization_code",
		"code":          code,
		"redirect_uri":  redirectUrl,
		"scope":         "identify email connections",
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
	url, err := url.Parse(userInfoEndpoint + "@me")
	if err != nil {
		return "", err
	}

	request := &http.Request{
		URL:    url,
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

func getUser(id string) (DiscordUser, error) {
	var botToken = viper.GetString("discord.clientbot")

	url, err := url.Parse(userInfoEndpoint + id)
	if err != nil {
		return DiscordUser{}, err
	}

	request := &http.Request{
		URL:    url,
		Header: map[string][]string{"Authorization": {"Bot " + botToken}},
	}

	response, err := HttpClient.Do(request)
	if err != nil {
		return DiscordUser{}, err
	}
	defer response.Body.Close()

	if response.StatusCode == http.StatusNotFound {
		return DiscordUser{}, NoDiscordUser
	} else if response.StatusCode != http.StatusOK {
		return DiscordUser{}, errors.New("invalid response code from Discord (" + response.Status + ")")
	}

	discordResponse := DiscordUser{}
	err = json.NewDecoder(response.Body).Decode(&discordResponse)
	return discordResponse, err
}
