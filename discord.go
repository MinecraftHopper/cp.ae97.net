package main

import (
	"encoding/json"
	"errors"
	"github.com/MinecraftHopper/panel/env"
	"net/http"
	"net/url"
)

const DiscordEndpoint = "https://discord.com/api/oauth2/token"
const userInfoEndpoint string = "https://discord.com/api/v10/users/"
const UserAgent string = "DiscordBot (https://github.com/MinecraftHopper, v0)"

var NoDiscordUser = errors.New("no discord user")

func redeemCode(code string) (string, error) {
	clientId := env.Get("discord.clientid")
	clientSecret := env.Get("discord.clientsecret")
	redirectUrl := env.Get("web.host") + "/login-callback"

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
	u, err := url.Parse(userInfoEndpoint + "@me")
	if err != nil {
		return "", err
	}

	request := &http.Request{
		URL:    u,
		Header: map[string][]string{"Authorization": {"Bearer " + accessToken}, "User-Agent": {UserAgent}},
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
	var botToken = env.Get("discord.clientbot")

	u, err := url.Parse(userInfoEndpoint + id)
	if err != nil {
		return DiscordUser{}, err
	}

	request := &http.Request{
		URL:    u,
		Header: map[string][]string{"Authorization": {"Bot " + botToken}, "User-Agent": {UserAgent}},
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
