AI Helper Plugin
======

Version 1.2

> This plugin is designed to integrate AI functionality into Chamilo, providing tools for generating educational content, such as quizzes or learning paths, using AI providers like OpenAI or DeepSeek.

---

### Overview

The AI Helper plugin integrates into parts of the Chamilo platform that are most useful to teachers/trainers or learners. It allows pre-generating content, letting teachers/trainers review it before publishing.

Currently, this plugin is integrated into:

- **Exercises:** In the Aiken import form, with options to generate questions using OpenAI or DeepSeek.
- **Learnpaths:** Option to create structured learning paths with OpenAI or DeepSeek.

---

### Supported AI Providers

#### OpenAI/ChatGPT
The plugin, created in early 2023, supports OpenAI's ChatGPT API.
- **Setup:**
1. Create an account at [OpenAI](https://platform.openai.com/signup) (or login if you already have one).
2. Generate a secret key at [API Keys](https://platform.openai.com/account/api-keys).
3. Click "Create new secret key", copy the key, and paste it into the "API key" field in the plugin configuration.

#### DeepSeek
DeepSeek is an alternative Open Source AI provider.
- **Setup:**
1. Create an account at [DeepSeek](https://www.deepseek.com/) (or login if you already have one).
2. Generate an API key at [API Keys](https://platform.deepseek.com/api_keys).
3. Click "Create new API key", copy the key, and paste it into the "API key" field in the plugin configuration.

---

### Features

- Generate quizzes in the Aiken format using AI.
- Create structured learning paths with AI assistance.
- Support for multiple AI providers, enabling easy switching between OpenAI and DeepSeek.
- Tracks API requests for monitoring usage and limits.

---

### Database Requirements

No additional database changes are required for v1.2.  
The existing table `plugin_ai_helper_requests` is sufficient for tracking requests from both OpenAI and DeepSeek.

If you're updating from **v1.0**, ensure the following table exists:

```sql
CREATE TABLE plugin_ai_helper_requests (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  tool_name varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  requested_at datetime DEFAULT NULL,
  request_text varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  prompt_tokens int(11) NOT NULL,
  completion_tokens int(11) NOT NULL,
  total_tokens int(11) NOT NULL,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
```
If you got this update through Git, you will also need to run `composer install` to update the autoload mechanism.
