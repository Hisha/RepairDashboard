<?php

class ChartRenderer
{
    protected string $nodeBinary;
    protected string $scriptPath;
    protected string $workingDir;
    
    public function __construct()
    {
        $this->nodeBinary = '/usr/bin/node';
        $this->scriptPath = APP_ROOT . '/chart_renderer/render_chart.js';
        $this->workingDir = APP_ROOT . '/chart_renderer';
    }
    
    public function render(array $config, string $configFileName): string
    {
        $configDir = $this->workingDir . '/configs';
        $outputDir = $this->workingDir . '/output';
        
        if (!is_dir($configDir)) {
            mkdir($configDir, 0775, true);
        }
        
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }
        
        $configPath = $configDir . '/' . $configFileName;
        
        file_put_contents(
            $configPath,
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
        
        $cmd = sprintf(
            '%s %s %s 2>&1',
            escapeshellarg($this->nodeBinary),
            escapeshellarg($this->scriptPath),
            escapeshellarg($configPath)
            );
        
        $output = [];
        $returnCode = 0;
        
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new RuntimeException(
                "Chart render failed.\nCommand: {$cmd}\nOutput:\n" . implode("\n", $output)
            );
        }
        
        if (empty($config['output']) || !file_exists($config['output'])) {
            throw new RuntimeException('Chart file was not created: ' . ($config['output'] ?? 'unknown'));
        }
        
        return $config['output'];
    }
}