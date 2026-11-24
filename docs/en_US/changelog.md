---
layout: default
lang: en_US
---

# Lite Site Monitoring changelog

# 24/11/2026 - 1.0.0

- Check frequency configurable per device (cron expression, every 5 minutes by default)
- Sites checked in parallel, a single request per check, timeouts of 10 s (connection) and 20 s (request)
- Redirects are followed; success if the final HTTP code is 2xx or 3xx, or one of the configured expected codes
- Optional expected keyword in the page
- Immediate retry on failure and configurable number of consecutive failures before alert
- Without Internet access, sites are no longer marked offline: their last state is kept
- "Internal site" option for local network sites
- New commands: HTTP code, Error, SSL expiry, Internet access
- Numeric times in milliseconds, which can be historized; the former history of these commands, in seconds, is cleared during the update
- "Latency" is the total request time (the former value added up cumulative times)
- New widget (can be disabled), status light on the plugin page and summary of the last check in the device
- URL validation when saving
- Interface in French, English, German, Spanish and Italian
- Security fix: the URL is no longer passed to a system command
- Code cleanup and removal of unused files

# 17/06/2021 - Beta version

- Beta launch
- "OnLine/OffLine" and "Latency" features
