<?php

declare(strict_types=1);

namespace App\Response;

/**
 * View Response - renders a PHP template with data.
 * 
 * Supports a layout system: the template is rendered first,
 * its output is captured and injected into the layout as $content.
 * 
 * Usage in controller:
 *   return new ViewResponse('bike/detail', ['bike' => $bike]);
 */
class ViewResponse extends Response
{
    private string $template;
    private array $data;
    private string $layout;

    private const TEMPLATE_DIR = __DIR__ . '/../../templates/';

    public function __construct(
        string $template,
        array $data = [],
        int $statusCode = 200,
        string $layout = 'layouts/public'
    ) {
        parent::__construct($statusCode);
        $this->template = $template;
        $this->data = $data;
        $this->layout = $layout;

        $this->withHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Render without a layout (e.g. for partials or error pages).
     */
    public function withoutLayout(): static
    {
        $this->layout = '';

        return $this;
    }

    /**
     * Use a different layout.
     */
    public function withLayout(string $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    protected function sendBody(): void
    {
        $content = $this->renderTemplate($this->template, $this->data);

        if ($this->layout !== '') {
            $layoutData = array_merge($this->data, ['content' => $content]);
            echo $this->renderTemplate($this->layout, $layoutData);
        } else {
            echo $content;
        }
    }

    /**
     * Render a template file with extracted data.
     * 
     * Uses output buffering to capture the template output
     * instead of sending it directly.
     */
    private function renderTemplate(string $template, array $data): string
    {
        $file = self::TEMPLATE_DIR . $template . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        // Extract data as local variables for the template
        extract($data, EXTR_SKIP);

        ob_start();
        require $file;

        return ob_get_clean();
    }
}