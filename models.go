package main

import (
	"gorm.io/gorm"
	"strings"
)

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

type HJT struct {
	ID                  uint     `json:"id"`
	Name                string   `json:"name"`
	MatchCriteria       string   `json:"match_criteria"`
	Description         string   `json:"description"`
	Category            string   `json:"category"`
	Severity            Severity `json:"severity"`
	SeverityDescription string   `json:"severity_description" gorm:"-"`
}

type Severity int

var SeverityInfo Severity = 0
var SeverityLow Severity = 1
var SeverityMedium Severity = 2
var SeverityHigh Severity = 3

func (s Severity) ToString() string {
	switch s {
	case SeverityHigh:
		return "High"
	case SeverityLow:
		return "Low"
	case SeverityMedium:
		return "Medium"
	default:
		return "Info"
	}
}

func SeverityFromString(s string) Severity {
	switch strings.ToLower(s) {
	case "high":
		return SeverityHigh
	case "medium":
		return SeverityMedium
	case "low":
		return SeverityLow
	default:
		return SeverityInfo
	}
}

func (h *HJT) AfterFind(tx *gorm.DB) (err error) {
	h.SeverityDescription = h.Severity.ToString()
	if h.Name == "" {
		h.Name = h.MatchCriteria
	}
	return
}
