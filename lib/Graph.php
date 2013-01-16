<?php

class Graph {
    private $title = "";
    private $data = array();
    private $maxValue = 0;

    private $pecision = 0;
    private $outerMin = 20;

    private $autoMaxValue = true;

    private $showLabel = true;
    private $showTitle = false;
    private $showValue = true;
    private $hideNullValue = true;

    private $barWidth = 30;
    private $barMargin = 15;
    private $graphHeight = 200;

    public function setData(array $data) {
        $this->data = $data;
        return $this;
    }

    public function setTitle($title) {
        $this->title = (string) $title;
        $this->showTitle(true);
        return $this;
    }

    public function showTitle($value) {
        $this->showTitle = (boolean) $value;
        return $this;
    }

    public function showLabel($value) {
        $this->showLabel = (boolean) $value;
        return $this;
    }

    public function showValue($value) {
        $this->showValue = (boolean) $value;
        return $this;
    }

    public function setBarWidth($width) {
        $this->barWidth = (int) $width;
        return $this;
    }

    public function setBarMargin($margin) {
        $this->barMargin = (int) $margin;
        return $this;
    }

    public function setMaxValue($value) {
        $this->maxValue = (float) $value;
        $this->autoMaxValue(false);
        return $this;
    }

    public function autoMaxValue($value) {
        $this->autoMaxValue = (boolean) $value;
        return $this;
    }

    public function setGraphHeight($value) {
        $this->graphHeight = (int) $value;
        return $this;
    }

    public function setPrecision($value) {
        $this->precision = (int) $value;
        return $this;
    }

    public function setOuterMin($value) {
        $this->outerMin = (int) $value;
        return $this;
    }

    public function hideNullValue($value) {
        $this->hideNullValue = (boolean) $value;
        return $this;
    }

    public function render() {
        $html = '<div class="graph">';

        if ($this->showTitle) {
            $html .= '<div class="title">' . $this->title . '</div>';
        }

        $values = array_values($this->data);
        $labels = array_keys($this->data);
        $maxValue = $this->autoMaxValue ? max($values) : $this->maxValue;
        $maxValue = $maxValue < 1 ? 1 : $maxValue;
        $left = $this->barMargin;

        $graphHeight = $this->graphHeight + $this->barMargin;
        $graphWidth = count($values) * ($this->barWidth + $this->barMargin) + $this->barMargin;

        $html .= '<ol class="data" style="width: ' . $graphWidth . 'px; height: ' . $graphHeight . 'px;">';
        foreach ($values as $value) {
            $height = floor($value / $maxValue * $this->graphHeight);

            $html .= '<li class="bar" style="left: ' . $left . 'px; height: ' . $height . 'px; width: ' . $this->barWidth . 'px;">';
            if ($this->showValue && ($this->showNullValue || $value > 0)) {
                $html .= '<span class="value' . ($height < $this->outerMin ? " outer" : "") . '">' . round($value, $this->precision) . '</span>';
            }
            $html .= '</li>';

            $left += $this->barWidth + $this->barMargin;
        }
        $html .= '</ol>';

        if ($this->showLabel) {
            $margin = floor($this->barMargin / 2);
            $width = $this->barWidth + $this->barMargin;

            $html .= '<ol class="label" style="margin-left: ' . $margin . 'px;">';
            foreach ($labels as $label) {
                $html .= '<li style="width: ' . $width . 'px;">' . $label . '</li>';
            }
            $html .= '</ol>';
        }

        $html .= '</div>';
        return $html;
    }
}
