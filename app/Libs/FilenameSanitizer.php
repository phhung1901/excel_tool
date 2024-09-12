<?php
/**
 * Created by PhpStorm.
 * User: hocvt
 * Date: 2019-09-19
 * Time: 13:24
 */

namespace App\Libs;

class FilenameSanitizer
{
    /**
     * @var array
     */
    protected $illegalCharacters = [];

    /**
     * @var string
     */
    protected $filename;

    public function __construct(string $filename = '')
    {
        $this->initializeIllegalCharacters();

        $this->setFilename($filename);
    }

    public static function make_safe_file_name($string, $limit = 95)
    {
        if (preg_match("/\.(\w{1,5})$/", $string, $matches)) {
            $ext = '.'.$matches[1];
            $string = mb_substr($string, 0, mb_strlen($string) - mb_strlen($ext));
            $limit = $limit - mb_strlen($ext);
        } else {
            $ext = '';
        }
        $safe_name = (new self())
            ->setFilename($string)
            ->stripIllegalFilesystemCharacters()
            ->getFilename();

        return mb_substr($safe_name, 0, $limit).$ext;
    }

    /**
     * Specify which characters shall be considered illegal (that is, would
     * cause an error or exception if included in a filename) across the
     * target platforms.
     *
     * @return $this
     *
     * @see: https://kb.acronis.com/content/39790
     *
     * @see: https://stackoverflow.com/questions/1976007/what-characters-are-forbidden-in-windows-and-linux-directory-names
     *
     * @see: https://superuser.com/questions/204287/what-characters-are-forbidden-in-os-x-filenames
     */
    protected function initializeIllegalCharacters()
    {
        $this->illegalCharacters['unix'] = [
            '/',
            chr(0),
        ];

        $this->illegalCharacters['windows'] = [
            '<',
            '>',
            ':',
            '"',
            '/',
            '\\',
            '|',
            '?',
            '*',
        ];

        // 0-31 (ASCII control characters)

        for ($i = 0; $i < 32; $i++) {
            $this->illegalCharacters['windows'][] = chr($i);
        }

        $this->illegalCharacters['macos'] = [
            ':',
        ];

        return $this;
    }

    /**
     * @return array
     */
    public function getIllegalCharacters()
    {
        return $this->illegalCharacters;
    }

    /**
     * @return $this
     */
    public function setFilename(string $filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Strip PHP tags and any encapsulated code; while a closing tag is not
     * required for code following an open tag to be stripped, it will be
     * stripped, too, if present. Short open tags are treated the same as long.
     *
     * @return $this
     */
    public function stripPhp()
    {
        $this->setFilename(filter_var($this->getFilename(), FILTER_SANITIZE_STRING));

        return $this;
    }

    /**
     * Strip characters that might be considered risky and therefore prone to
     * abuse through various injection-style attacks.
     *
     * @return $this
     */
    public function stripRiskyCharacters()
    {
        $options = [
            'flags' => FILTER_FLAG_STRIP_BACKTICK | FILTER_FLAG_STRIP_LOW,
        ];

        $this->setFilename(
            filter_var($this->getFilename(), FILTER_SANITIZE_STRING, $options)
        );

        return $this;
    }

    /**
     * Strip illegal filesystem characters.
     *
     * @return $this
     */
    public function stripIllegalFilesystemCharacters()
    {
        $illegalCharacters = $this->getIllegalCharacters();

        $illegalCharactersAsString = implode('', array_merge(
            $illegalCharacters['unix'],
            $illegalCharacters['windows'],
            $illegalCharacters['macos']
        ));

        $escapedRegex = preg_quote($illegalCharactersAsString, '/');

        $this->setFilename(preg_replace('/['.$escapedRegex.']/', '', $this->getFilename()));

        return $this;
    }
}
