<?php

namespace App\Ai\Gemini\Sessions;

use App\Ai\AiClientInterface;
use App\Ai\Entities\ObjectInfo;
use App\Ai\Enum\AiModelEnum;
use App\Ai\Enum\AiService;
use Gemini\Client;
use Gemini\Enums\ModelType;
use Gemini\Resources\ChatSession;

class GeminiSession implements AiClientInterface
{
    use HasAiUtilities;

    public ChatSession $session;

    protected int $last_ask = 0;

    public function __construct(
        protected         $api_key,
        protected     AiModelEnum    $model = AiModelEnum::GEMINI_PRO,
        protected int     $delay = 1000,
        array $history = [],
    )
    {
        $this->session = $this->makeSession(
            $this->api_key,
            $this->model,
            $history);
    }

    protected function makeSession(string $api_key, AiModelEnum $model, array $history = []): ChatSession
    {
        return \Gemini::client($api_key)
            ->generativeModel(model: $model->toGeminiModel())
            ->startChat($history);
    }

    public function chat($message, $retry = 1, $usleep = 2000, ?callable $on_error = null): string
    {
        $this->checkDelay();
        start:
        try {
            $result = $this->session->sendMessage($message);
        } catch (\Exception $ex) {
            unset($this->session->history[count($this->session->history) - 1]);
            if ($on_error) {
                call_user_func($on_error, $ex);
            }
            if ($retry > 0) {
                usleep($usleep);
                $retry--;
                goto start;
            }
            throw $ex;
        }
        $text_result = $result->text();
        return $text_result;
    }

    public function ask($message, $retry = 1, $usleep = 2, ?callable $on_error = null, $history = null, $instruction = null): string
    {
        $this->checkDelay();
        start:
        try {
            $session = $this->makeSession($this->api_key, $this->model, $history ?: []);
            $result = $session->sendMessage($message);
        } catch (\Exception $ex) {
            if ($on_error) {
                call_user_func($on_error, $ex);
            }
            if ($retry > 0) {
                usleep($usleep);
                $retry--;
                goto start;
            }
            throw $ex;
        }
        $text_result = $result->text();
        return $text_result;
    }

    public function getModel(): AiModelEnum
    {
        return AiModelEnum::tryFrom($this->model->value);
    }

    public function getService(): AiService
    {
        return AiService::GEMINI;
    }

    protected function checkDelay(): void
    {
        $now = (int)(microtime(true) * 1000);
        $wait = $this->delay - ($now - $this->last_ask);
        if ($wait > 0) {
            usleep($wait);
        }
        $this->last_ask = $now;
    }
}
