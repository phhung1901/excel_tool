<?php

namespace App\Ai\Enum;

use Gemini\Enums\ModelType;

enum AiModelEnum: string
{

    case NULL = 'null';

    case GEMINI_PRO = 'models/gemini-pro';
    case GEMINI_PRO_VISION = 'models/gemini-pro-vision';
    case GEMINI_EMBEDDING = 'models/embedding-001';


    case GPT_35_TURBO = 'gpt-3.5-turbo';
    case GPT_4 = 'gpt-4';


    public function toGeminiModel(): ModelType
    {
        return ModelType::tryFrom((string)$this->value);
    }

    public function toOpenAiModel(): string
    {
        return $this->value;
    }

    public static function defaultModel(AiService $service): self
    {
        return match ($service){
            AiService::GEMINI => self::GEMINI_PRO,
            AiService::CHAT_GPT => self::GPT_35_TURBO,
            AiService::NULL => self::NULL,
            default => throw new \InvalidArgumentException("no default model for service " . $service->value),
        };
    }

}
