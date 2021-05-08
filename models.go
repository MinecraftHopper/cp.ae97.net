package main

type Factoid struct {
	Name    string `json:"name"`
	Content string `json:"content"`
}

type Error struct {
	Message string `json:"message"`
}

type Permission struct {
	DiscordId  string `gorm:"discord_id"`
	Permission string `gorm:"permission"`
}

type DiscordOAuth2Response struct {
	AccessToken  string `json:"access_token"`
	TokenType    string `json:"token_type"`
	ExpiresIn    uint64 `json:"expires_in"`
	RefreshToken string `json:"refresh_token"`
	Scope        string `json:"scope"`
}

type DiscordUser struct {
	Id            string `json:"id"`
	Username      string `json:"username"`
	Discriminator string `json:"discriminator"`
	AvatarHash    string `json:"avatar"`
}
