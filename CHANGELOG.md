# Changelog

## 3.0.1 (2026-10-26)

- Send the TC3 timestamp header as a string, preserving its value and signing input. This fixes the Guzzle 7.11+ deprecated non-string header warning under strict PHP 8.5/E_ALL.
- Exercise CommonClient signing, JSON serialization and response parsing through the real Guzzle transport stack using synthetic credentials.
