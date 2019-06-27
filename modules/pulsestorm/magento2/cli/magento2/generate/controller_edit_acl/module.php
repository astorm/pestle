<?php
namespace Pulsestorm\Magento2\Cli\Magento2\Generate\Controller_Edit_Acl;
use function Pulsestorm\Pestle\Importer\pestle_import;
pestle_import('Pulsestorm\Pestle\Library\output');
pestle_import('Pulsestorm\Cli\Token_Parse\pestle_token_get_all');
pestle_import('Pulsestorm\Pestle\Library\writeStringToFile');

class TokenParser
{
    protected $position=0;
    protected $tokens;

    protected function replaceCurrentToken($token)
    {
        $this->tokens[$this->position] = $token;
    }

    public function setStringContents($contents)
    {
        $this->tokens   = pestle_token_get_all($contents);
    }

    public function getCurrentToken()
    {
        return $this->tokens[$this->position];
    }

    public function isAtEnd()
    {
        return count($this->tokens) === ($this->position + 1);
    }

    public function goNext()
    {
        $this->position++;
        if(array_key_exists($this->position, $this->tokens))
        {
            return $this->getCurrentToken();
        }
        $this->position--;
        return null;
    }

    public function getClassString()
    {
        $values = array_map(function($token){
            if(isset($token->token_value))
            {
                return $token->token_value;
            }
            return '';
        }, $this->tokens);
        return implode('',  $values);
    }
}

class EditConstantTokenParser extends TokenParser
{
    private function scanToString($string)
    {
        while($token=$this->goNext())
        {
            if($token->token_value === $string)
            {
                return;
            }
        }

    }

    private function isPositionAtClassConstant()
    {
        for($i=$this->position;$i--;$i>0)
        {
            $token = $this->tokens[$i];
            if($token->token_name === 'T_WHITESPACE') { continue; }
            return $token->token_name === 'T_CONST';
        }
        return null;
    }

    private function scanToNamedConstant($constantName)
    {
        $this->scanToString($constantName);
        if($this->isPositionAtClassConstant())
        {
            return true;
        }

        if($this->isAtEnd())
        {
            return false;
        }
        return $this->scanToNamedConstant($constantName);
    }

    private function getSingleQuotedPhpString($string)
    {
        $string = str_replace("'", "\\'", $string);
        if($string[strlen($string) -1] === '\\')
        {
            $string .= '\\';
        }

        return "'$string'";

    }

    public function replaceConstantStringValue($constantName, $value)
    {
        $this->scanToNamedConstant($constantName);
        $token = $this->getCurrentToken();
        if($token->token_value !== $constantName)
        {
            return false;
        }

        while($token = $this->goNext())
        {
            // if($token->token_name === 'T_WHITESPACE') { continue; }
            if($token->token_value !== ';')
            {
                $this->replaceCurrentToken(null);
                continue;
            }

            //splice in new tokens
            $equalsToken = new \stdClass;
            $equalsToken->token_value = '=';
            $equalsToken->token_name  = 'T_SINGLE_CHAR';

            $replacementToken = new \stdClass;
            $replacementToken->token_value = $this->getSingleQuotedPhpString($value);
            $replacementToken->token_name = 'T_CONSTANT_ENCAPSED_STRING';

            array_splice($this->tokens, $this->position, 0, [
                $equalsToken, $replacementToken
            ]);
            break; //hit the ;, break out
        }

        return true;
    }
}

/**
* Edits the const ADMIN_RESOURCE value of an admin controller
*
* @command magento2:generate:controller-edit-acl
* @argument path_controller Path to Admin Controller
* @argument acl_rule ACL Rule
*/
function pestle_cli($argv)
{
    $contents = file_get_contents($argv['path_controller']);
    $parser = new EditConstantTokenParser;
    $parser->setStringContents($contents);
    if($parser->replaceConstantStringValue('ADMIN_RESOURCE', $argv['acl_rule']))
    {
        writeStringToFile($argv['path_controller'], $parser->getClassString());
        output("ADMIN_RESOURCE constant value changed");
    }
    else
    {
        output("No ADMIN_RESOURCE constant in class file");
    }


}
