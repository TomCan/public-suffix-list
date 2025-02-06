<?php

    $url = 'https://publicsuffix.org/list/public_suffix_list.dat';
    $icann_end = '// ===END ICANN DOMAINS===';

    $icann_list = [];
    $private_list = [];

    $icann = true;
    $list = file_get_contents($url);
    foreach (explode("\n", $list) as $line) {
        if (trim($line) != '' && substr($line, 0, 2) != '//') {
            if ($icann) {
                $icann_list[] = trim($line);
            } else {
                $private_list[] = trim($line);
            }
        } elseif (trim($line) == $icann_end) {
            // end of ICANN list
            $icann = false;
        }
    }

    // write classes
    $name = 'PSLIcann';
    $values = "'icann' => array('".implode("', '", $icann_list)."')";
    $content = <<<EOF
<?php

namespace TomCan\PublicSuffixList;

class $name extends AbstractPSL
{
    protected \$lists = array($values);
}
EOF;
    file_put_contents(__DIR__.'/../src/tomcan/'.$name.'.php', $content);

    $name = 'PSLFull';
    $values = "'icann' => array('".implode("', '", $icann_list)."'), 'private' => array('".implode("', '", $private_list)."')";
    $content = <<<EOF
<?php

namespace TomCan\PublicSuffixList;

class $name extends AbstractPSL
{
    protected \$lists = array($values);
}
EOF;
    file_put_contents(__DIR__.'/../src/tomcan/'.$name.'.php', $content);