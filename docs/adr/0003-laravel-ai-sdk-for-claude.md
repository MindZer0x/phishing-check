# Claude via the laravel/ai SDK

The AI analysis talks to Claude through `laravel/ai`, the official Laravel AI SDK, rather than the lower-level `anthropic-ai/sdk` or the community package Prism. It is idiomatic Laravel (configuration, structured output, built-in test fakes), which keeps the feature tests free of hand-written HTTP mocks. The default model is `claude-haiku-5-5`: the task is short classification and explanation, where speed and cost matter more than deep reasoning. The model is configurable via `.env`.

## Considered Options

- **anthropic-ai/sdk**: full access to every API feature, but more glue code and no Laravel-native faking.
- **Prism**: mature and provider-agnostic, but a third-party dependency where an official package now exists.
