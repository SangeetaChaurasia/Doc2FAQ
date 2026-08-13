<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Gets the path to the configuration directory.
     */
    private function getConfigDir(): string
    {
        return $this->getProjectDir().'/config';
    }

    /**
     * Gets the path to the build directory.
     */
    private function getBuildDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }

    /**
     * Gets the path to the logs directory.
     */
    private function getLogDir(): string
    {
        return $this->getProjectDir().'/var/log';
    }

    /**
     * Returns the kernel parameters.
     */
    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();
        $parameters['kernel.cache_dir'] = $this->getBuildDir();
        $parameters['kernel.build_dir'] = $this->getBuildDir();
        $parameters['kernel.logs_dir'] = $this->getLogDir();

        return $parameters;
    }

    public function getCacheDir(): string
    {
        return $this->getBuildDir();
    }
}
