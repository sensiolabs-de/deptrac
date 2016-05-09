<?php 

namespace SensioLabs\Deptrac\ConfigurationEngine\Twig;

class YamlTwigExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'deptrac_yaml';
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'yaml', function($data) {
                    return json_encode('!!deptrac_'.$data);
                },
                ['is_safe' => ['html']]
            )
        ];
    }


}