<?php
namespace ZipkinHelper;

class ZipkinSpan
{
    private $span;
    private $cs;

    public function __construct($traceId, $id, $parentId, $name, $kind, $localEndpoint, $tags, $remoteEndpoint)
    {
        $timestamp = $this->timestamp();
        $this->cs = $kind === 'CLIENT' ? 'c' : 's';
        $this->span = array(
            'traceId' => $traceId,
            'id' => $id,
            'name' => $name,
            'kind' => $kind,
            'timestamp' => $timestamp,
            'duration' => 0,
            'debug' => true,
            'shared' => true,
            'localEndpoint' => $localEndpoint,
            'annotations' => array(
                array(
                    'timestamp' => $timestamp,
                    'value' => ($kind === 'PRODUCER' ? 'ms' :
                        ($kind === 'CONSUMER' ? 'mr' : $this->cs .
                        ($kind === 'CLIENT' ? 'r' : 's'))),
                ),
            ),
        );
        if ( ! empty($parentId))
        {
            $this->span['parentId'] = $parentId;
        }
        if ( ! empty($remoteEndpoint))
        {
            $this->span['remoteEndpoint'] = $remoteEndpoint;
        }
        if ( ! empty($tags))
        {
            $this->span['tags'] = $tags;
        }
    }

    public function end()
    {
        $timestamp = $this->timestamp();
        $this->span['duration'] = $timestamp - $this->span['timestamp'];
        $this->span['annotations'][] = array(
            'timestamp' => $timestamp,
            'value' => $this->cs . ($this->cs === 'c' ? 's' : 'r'),
        );
    }

    public function get()
    {
        return $this->span;
    }

    private function timestamp()
    {
        return intval(microtime(true) * 1000 * 1000);
    }
}