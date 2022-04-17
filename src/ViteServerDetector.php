<?php

declare(strict_types=1);

namespace Dakujem\Peat;

use Throwable;

/**
 * @experimental
 * @deprecated Unfinished / not usable for production yet.
 *
 * A detector that tries to tell if the dev server is live.
 *
 * WARNING
 * This currently does not work properly. It causes 1-2 second rendering hang, making it more-or-less useless.
 *
 * @author Andrej Rypak <xrypak@gmail.com>
 */
final class ViteServerDetector
{
    /** @var callable */
    private $detector;
    private ?bool $cached = null;

    public function __construct(callable $detector)
    {
        $this->detector = $detector;
    }

    public function __invoke(): bool
    {
        $this->cached ??= (bool)($this->detector)();
        return $this->cached;
    }

    public static function usingCurl(string $url): callable
    {
        return new self(
            fn(): bool => self::detectLiveServerUsingCurl($url)
        );
    }

    /**
     * @experimental
     */
    public static function usingCurlWithDockerProxySupport(string $url): callable
    {
        // Note:
        //   For linux, you need to start the container with `--add-host host.docker.internal:host-gateway`
        //   for this to work.
        return new self(
            fn(): bool => // check 2 urls:
                // 1. check directly the provided URL for native environments
                self::detectLiveServerUsingCurl($url) ||
                // 2. check localhost Docker proxy for Win/MacOS on docker
                self::detectLiveServerUsingCurl((function () use ($url): string {
                    $pieces = parse_url($url);
                    $pieces['host'] = 'host.docker.internal';
                    return sprintf("%s://%s%s", $pieces['scheme'] ?? 'http', $pieces['host'], $pieces['path'] ?? '');
                })())
        );
    }

    /**
     * @experimental
     */
    public static function detectLiveServerUsingCurl(string $url): bool
    {
        // It looks very suboptimal to use curl, but laravel-vite does it, so we could too?
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, rtrim($url, '/') . '/' . ViteServerLocator::CLIENT_SCRIPT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0.5); // <1 s
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, .010); // 10 ms
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 10 ms

        $result = curl_exec($ch);
        curl_close($ch);

        return !!$result;
    }

    /**
     * @experimental
     */
    public static function detectLiveServerUsingFsuckopen(string $url): bool
    {
        // TODO there is a hundred year old bug in PHP, fsockopen does not work with `localhost`
        try {
            $parts = parse_url($url);
//            xdebug_break();
//            $r = fopen($url, 'r');

            $rc = @fsockopen($parts['host'], $parts['port'] ?? 80, $code, $msg, .010); // 10 milisekund;
            try {
                return $rc !== false && $rc !== null;
            } finally {
                @fclose($rc);
            }
        } catch (Throwable $e) {
            return false;
        }
    }
}
