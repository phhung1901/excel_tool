<?php

namespace App\Ai;

use App\Ai\Enum\AiModelEnum;
use App\Ai\Enum\AiService;

interface AiClientInterface
{
    public function getService(): AiService;

    public function getModel(): AiModelEnum;

    /**
     * Hỏi theo phiên, tự động thêm hỏi/đáp hiện tại vào history
     */
    public function chat($message, $retry = 1, $usleep = 2, ?callable $on_error = null): string;

    /**
     * Hỏi riêng lẻ với history tùy biến
     */
    public function ask($message, $retry = 1, $usleep = 2, ?callable $on_error = null, $history = null, $instruction = null): string;

}
