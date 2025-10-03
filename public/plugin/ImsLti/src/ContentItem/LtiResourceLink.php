<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;

/**
 * Class LtiContentItem.
 */
class LtiResourceLink extends LtiContentItemType
{
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $text;
    /**
     * @var stdClass
     */
    private $icon;
    /**
     * @var stdClass
     */
    private $thumbnail;
    /**
     * @var stdClass
     */
    private $iframe;
    /**
     * @var array
     */
    private $custom;
    /**
     * @var stdClass
     */
    private $lineItem;
    /**
     * @var stdClass
     */
    private $available;
    /**
     * @var stdClass
     */
    private $submission;

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @return ImsLtiTool
     */
    public function save(ImsLtiTool $baseTool, Course $course)
    {
        $newTool = $this->createTool($baseTool);
        $newTool->setActiveDeepLinking(false);

        $em = Database::getManager();

        $em->persist($newTool);
        $em->flush();

        ImsLtiPlugin::create()->addCourseTool($course, $newTool);
    }

    /**
     * @throws Exception
     */
    protected function validateItemData(stdClass $itemData)
    {
        $this->url = empty($itemData->url) ? '' : $itemData->url;
        $this->title = empty($itemData->title) ? '' : $itemData->title;
        $this->text = empty($itemData->text) ? '' : $itemData->text;
        $this->custom = empty($itemData->custom) || !is_array($itemData->custom) ? [] : (array) $itemData->custom;

        $this->icon = empty($itemData->icon) ? null : $itemData->icon;

        if ($this->icon
            && (empty($this->icon->url) || empty($this->icon->width) || empty($this->icon->height))
        ) {
            throw new Exception(sprintf("Icon properties are missing in data form content item: %s", print_r($itemData, true)));
        }

        $this->thumbnail = empty($itemData->thumbnail) ? null : $itemData->thumbnail;

        if ($this->thumbnail
            && (empty($this->thumbnail->url) || empty($this->thumbnail->width) || empty($this->thumbnail->height))
        ) {
            throw new Exception(sprintf("Thumbnail URL is missing in data form content item: %s", print_r($itemData, true)));
        }

        $this->iframe = empty($itemData->iframe) ? null : $itemData->iframe;

        if ($this->iframe && (empty($this->iframe->width) || empty($this->iframe->height))) {
            throw new Exception(sprintf("Iframe size is wrong in data form content item: %s", print_r($itemData, true)));
        }

        $this->lineItem = empty($itemData->lineItem) ? null : $itemData->lineItem;

        if ($this->lineItem && empty($this->lineItem->scoreMaximum)) {
            throw new Exception(sprintf("LineItem properties are missing in data form content item: %s", print_r($itemData, true)));
        }

        $this->available = empty($itemData->available) ? null : $itemData->available;

        if ($this->available && empty($this->available->startDateTime) && empty($this->available->endDateTime)) {
            throw new Exception(sprintf("LineItem properties are missing in data form content item: %s", print_r($itemData, true)));
        }

        $this->submission = empty($itemData->submission) ? null : $itemData->submission;

        if ($this->submission && empty($this->submission->startDateTime) && empty($this->submission->endDateTime)) {
            throw new Exception(sprintf("Submission properties are missing in data form content item: %s", print_r($itemData, true)));
        }
    }

    /**
     * @return ImsLtiTool
     */
    private function createTool(ImsLtiTool $baseTool)
    {
        $newTool = clone $baseTool;
        $newTool->setParent($baseTool);

        if (!empty($this->url)) {
            $newTool->setLaunchUrl($this->url);
        }

        if (!empty($this->title)) {
            $newTool->setName($this->title);
        }

        if (!empty($this->text)) {
            $newTool->setDescription($this->text);
        }

        if (!empty($this->custom)) {
            $newTool->setCustomParams(
                $newTool->encodeCustomParams($this->custom)
            );
        }

        return $newTool;
    }
}
