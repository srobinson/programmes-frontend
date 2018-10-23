<?php
declare(strict_types = 1);
namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Twig_Extension;
use Twig_Function;
use Twig_Environment;

class HtmlUtilitiesExtension extends Twig_Extension
{
    private $packages;
    private $twig;

    public function __construct(
        Packages $packages,
        Twig_Environment $twig
    ) {
        $this->packages = $packages;
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_Function('asset_js', [$this, 'assetJs']),
            new Twig_Function('build_css_classes', [$this, 'buildCssClasses']),
            new Twig_Function('build_html_attributes', [$this, 'buildHtmlAttributes']),
            new Twig_Function('truncate', [$this, 'truncate']),
            new Twig_Function('render_twig_file', [$this, 'renderTwigFile']),
        ];
    }

    /**
     * This is needed because the rendered template result is returned and not printed out, so it can be re-used
     * as input to other functions in twig files (like ds2013)
     *
     * @param string $fileName  The twig file path to render
     * @param array  $arguments The list of arguments to pass to the template for rendering
     *
     * @return string
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderTwigFile($fileName, $arguments)
    {
        return $this->twig->render($fileName, $arguments);
    }

    /**
     * Given a js asset path, return it in a format suitable for inclusion in
     * the require config (i.e. with the ".js" extension removed)
     */
    public function assetJs(string $path): string
    {
        return preg_replace('/\.js$/', '', $this->packages->getUrl($path, null));
    }

    public function buildCssClasses(array $cssClassTests = []): string
    {
        $cssClasses = [];
        foreach ($cssClassTests as $cssClass => $shouldSet) {
            if ($shouldSet) {
                $cssClasses[] = $cssClass;
            }
        }
        return trim(implode(' ', $cssClasses));
    }

    public function buildHtmlAttributes(array $htmlAttributes): string
    {
        $attrs = [];
        foreach ($htmlAttributes as $attributeName => $attributeValue) {
            $attrs[] = htmlspecialchars($attributeName, ENT_HTML5) . '="' . htmlspecialchars($attributeValue, ENT_HTML5) . '"';
        }
        return trim(implode(' ', $attrs));
    }

    /**
     * Smart(ish) truncate.
     * Takes a string and truncates it to no more than the required character length,
     * but to the previous space to prevent breaking up words.
     *
     * @param string $string the string to be shortened.
     * @param int|null $length max number of characters the string should be (null = no truncate)
     * @param string $suffix string to override the continuation character (default ellipsis)
     * @return string
     */
    public function truncate(string $string, ?int $length, string $suffix = '…')
    {
        if (!$length || mb_strlen($string) <= $length) {
            return $string;
        }

        return mb_substr($string, 0, mb_strrpos($string, ' ', - (mb_strlen($string) - $length))) . $suffix;
    }
}
