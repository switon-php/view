<?php

declare(strict_types=1);

namespace Switon\Viewing;

use Switon\Core\Attribute\Autowired;
use Switon\Core\ContainerInterface;
use Switon\Core\Lazy;
use Switon\Core\PathAliasInterface;
use Switon\Core\ServiceProviderInterface;
use Switon\Eventing\ListenerProviderInterface;

/**
 * Registers and boots view integration services.
 *
 * Use when the application should enable the view rendering listener and default @view alias wiring.
 *
 * Road-signs:
 * - boot(): listener registration + default @view alias
 *
 * @see \Switon\Core\ServiceProviderInterface
 */
class ServiceProvider implements ServiceProviderInterface
{
    #[Autowired] protected ListenerProviderInterface $listenerProvider;

    #[Autowired] protected PathAliasInterface|Lazy $pathAlias;

    /**
     * No container registrations are required; boot-time wiring is sufficient.
     */
    public function register(ContainerInterface $container): void
    {
        // No service registration needed
    }

    /**
     * Register the request-rendering bridge and the fallback <code>@view</code> alias.
     */
    public function boot(): void
    {
        // Register view layer listener to bridge request rendering events to view rendering.
        $this->listenerProvider->register(ViewRenderer::class);

        if (!$this->pathAlias->has('@view')) {
            $this->pathAlias->set('@view', '@app/View');
        }
    }
}
