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

    $meta = [];
    $data = explode("\n", file_get_contents(__DIR__ . '/meta.txt'));
    foreach ($data as $line) {
        $var = explode('=', $line);
        if (count($var) > 1) {
            $meta[$var[0]] = $var[1];
        }
    }
    $updated = false;

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

    if (sha1($content) != $meta['icann']) {
        $updated = true;
        $meta['icann'] = sha1($content);
        file_put_contents(__DIR__.'/../src/'.$name.'.php', $content);
    }

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

    if (sha1($content) != $meta['full']) {
        $updated = true;
        $meta['full'] = sha1($content);
        file_put_contents(__DIR__.'/../src/'.$name.'.php', $content);
    }

    if ($updated) {
        // version bump
        $version = explode('.', $meta['version']);
        $version[count($version) - 1] += 1;
        $meta['version'] = implode('.', $version);

        // rewrite meta.txt
        $content = '';
        foreach ($meta as $key => $value) {
            $content .= "$key=$value\n";
        }
        file_put_contents(__DIR__.'/meta.txt', $content);
    }