<?php
namespace Mindweb\DeviceDetectorModifier;

use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;
use Mindweb\Modifier;
use Doctrine\Common\Cache\PhpFileCache;

class DeviceDetectorModifier implements Modifier\Modifier
{
    /**
     * @var bool
     */
    private $isCaching;

    /**
     * @var string
     */
    private $cachePath;

    /**
     * @var bool
     */
    private $discardBotInformation;

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->isCaching = !empty($configuration['cachePath']);
        $this->cachePath = !empty($configuration['cachePath']) ? $configuration['cachePath'] : '';
        $this->discardBotInformation = isset($configuration['discardBotInformation'])
            && $configuration['discardBotInformation'] === true;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modify(array $data)
    {
        DeviceParserAbstract::setVersionTruncation(DeviceParserAbstract::VERSION_TRUNCATION_NONE);

        $dd = new DeviceDetector($data['userAgent']);

        if ($this->isCaching) {
            $dd->setCache(new PhpFileCache($this->cachePath));
        }

        if ($this->discardBotInformation) {
            $dd->discardBotInformation();
        }

        $dd->parse();

        if ($dd->isBot()) {
            $data['device'] = array(
                'bot' => true,
                'info' => $dd->getBot()
            );
        } else {
            $data['device'] = array(
                'bot' => false,
                'client' => $dd->getClient(),
                'os' => $dd->getOs(),
                'device' => $dd->getDevice(),
                'brand' => $dd->getBrand(),
                'model' => $dd->getModel()
            );
        }

        return $data;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 10;
    }
} 