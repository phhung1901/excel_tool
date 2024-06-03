<?php
namespace App\Services\Ai\Gemini;

use App\Models\Keyword;
use Gemini\Data\Content;
use Gemini\Enums\Role;

class GeminiClient
{
    public $client;
    public string $keyword;

    public function __construct(string $keyword)
    {
        $this->client = \Gemini::client(getenv('AI_GEMINI_API'));
        $this->keyword = $keyword;
        $this->client = $this->client->geminiPro()
            ->startChat(history: [
                Content::parse('Keyword'),
                Content::parse($keyword, role: Role::MODEL),
                Content::parse('Yêu cầu'),
                Content::parse('Thực hiện POS tagging với keyword '. $keyword, role: Role::MODEL),
                Content::parse('Kết quả'),
                Content::parse('Trả về kết quả dưới dạng json với key là thứ tự của từ trong câu để sau có thể sử dụng để sắp xếp lại câu cho đúng
                    và value dạng {type: loại từ(ADI, N, V, ...), word: từ được phân loại}', role: Role::MODEL),
                Content::parse('Lưu ý'),
                Content::parse('Lưu ý từ có thể là các từ ghép hoặc từ đơn. Ưu tiên tách thành các từ ghép', role: Role::MODEL),
            ]);
    }

    public function genPOS()
    {
        $rs = $this->client->sendMessage($this->keyword);
        preg_match('/```json(.*?)```/s', preg_replace('/\n/', '', $rs->text()), $matches);
        if (count($matches)) {
            $pos = json_decode($matches[1], true);
            return $pos;
        }
        return null;
    }
}
