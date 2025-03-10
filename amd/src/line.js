// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/* eslint max-depth: ["error", 8] */

/**
 * Library of classes for handling lines and points.
 *
 * These classes can represent Points and line, let you alter them
 * and can give you an SVG representation.
 *
 * @module     qtype_drawlines/line
 * @copyright  2024 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(function() {

    "use strict";

    /**
     * A point, with x and y coordinates.
     *
     * @param {int} x centre X.
     * @param {int} y centre Y.
     * @constructor
     */
    function Point(x, y) {
        this.x = x;
        this.y = y;
    }

    /**
     * Standard toString method.
     *
     * @returns {string} "x;y";
     */
    Point.prototype.toString = function() {
        return this.x + ',' + this.y;
    };

    /**
     * Move a point
     *
     * @param {int} dx x offset
     * @param {int} dy y offset
     */
    Point.prototype.move = function(dx, dy) {
        this.x += dx;
        this.y += dy;
    };

    /**
     * Return a new point that is a certain position relative to this one.
     *
     * @param {(int|Point)} offsetX if a point, offset by these points coordinates, else and int x offset.
     * @param {int} [offsetY] used if offsetX is an int, the corresponding y offset.
     * @return {Point} the new point.
     */
    Point.prototype.offset = function(offsetX, offsetY) {
        if (offsetX instanceof Point) {
            offsetY = offsetX.y;
            offsetX = offsetX.x;
        }
        return new Point(this.x + offsetX, this.y + offsetY);
    };

    /**
     * Make a point from the string representation.
     *
     * @param {String} coordinates "x,y".
     * @return {Point} the point. Throws an exception if input is not valid.
     */
    Point.parse = function(coordinates) {
        var bits = coordinates.split(',');
        if (bits.length !== 2) {
            throw new Error(coordinates + ' is not a valid point');
        }
        return new Point(Math.round(bits[0]), Math.round(bits[1]));
    };

    /**
     * Line constructor. Class to represent the different types of drop zone shapes.
     *
     * @param {int} [x1] centre X1.
     * @param {int} [y1] centre Y1.
     * @param {int} [startRadius] startRadius.
     * @param {int} [x2] centre X2.
     * @param {int} [y2] centre Y2.
     * @param {int} [endRadius] endRadius.
     * @param {String} [lineType] Line type.
     * @param {String} [labelstart] start label of a line.
     * @param {String} [labelmiddle] middle label of a line.
     * @param {String} [labelend] end label of a line.
     * @constructor
     */
    function Line(x1, y1, startRadius, x2, y2, endRadius, lineType, labelstart, labelmiddle, labelend) {
        this.x1 = x1;
        this.y1 = y1;

        this.x2 = x2;
        this.y2 = y2;

        this.centre1 = new Point(x1, y1);
        this.centre2 = new Point(x2, y2);

        this.startRadius = startRadius;
        this.endRadius = endRadius;

        this.lineType = lineType;

        this.labelstart = labelstart;
        this.labelmiddle = labelmiddle;
        this.labelend = labelend;
    }
    Line.prototype = new Line();

    /**
     * Get the type of shape.
     *
     * @return {String} 'linesinglearrow', 'linedoublearrows', 'lineinfinite'.
     */
    Line.prototype.getType = function() {
        return this.lineType;
    };

    /**
     * Get the string representation of this shape.
     *
     * @return {String} coordinates as they need to be typed into the form.
     */
    Line.prototype.getCoordinates = function() {
        return [
            this.centre1.x + ',' + this.centre1.y + ';' + this.startRadius,
            this.centre2.x + ',' + this.centre2.y + ';' + this.endRadius
        ];
    };

    /**
     * Create the svg group with line.
     *
     * @param {SVGElement} svg the SVG graphic to add this shape to.
     * @param {int} bgImageWidth
     * @param {int} bgImageHeight
     * @return {SVGElement} SVG representation of this shape.
     */
    Line.prototype.makeSvg = function(svg, bgImageWidth, bgImageHeight) {
        addLineArrow(svg);
        var svgEl = createSvgShapeGroup(svg, 'polyline');
        this.updateSvg(svgEl, bgImageWidth, bgImageHeight);
        return svgEl;
    };

    /**
     * Update the SVG representation of this shape.
     *
     * @param {SVGElement} svgEl the SVG representation of this shape.
     * @param {int} bgImageWidth
     * @param {int} bgImageHeight
     */
    Line.prototype.updateSvg = function(svgEl, bgImageWidth, bgImageHeight) {
        // Set line attributes.
        this.drawLine(svgEl, bgImageWidth, bgImageHeight);

        // Set start and end circle attributes.
        svgEl.childNodes[1].setAttribute('cx', this.centre1.x);
        svgEl.childNodes[1].setAttribute('cy', this.centre1.y);
        svgEl.childNodes[1].setAttribute('r', Math.abs(this.startRadius));

        svgEl.childNodes[2].setAttribute('cx', this.centre2.x);
        svgEl.childNodes[2].setAttribute('cy', this.centre2.y);
        svgEl.childNodes[2].setAttribute('r', Math.abs(this.endRadius));

        // If the svg g element is already placed in dropzone, then add the keyboard support.
        var svgClass = svgEl.getAttribute('class');
        if (svgClass && svgClass.includes('placed')) {
            svgEl.childNodes[1].setAttribute('tabindex', '0');
            svgEl.childNodes[2].setAttribute('tabindex', '0');
        }
        this.updateSvgLabels(svgEl, bgImageWidth, bgImageHeight);
    };

    /**
     * Update the SVG representation of this shape.
     *
     * @param {SVGElement} svgEl the SVG representation of this shape.
     * @param {int} bgImageWidth
     * @param {int} bgImageHeight
     */
    Line.prototype.updateSvgLabels = function(svgEl, bgImageWidth, bgImageHeight) {
        // Set start and end label attributes.
        svgEl.childNodes[3].textContent = this.labelstart;
        this.adjustTextPosition(svgEl.childNodes[3], this.centre1.x, this.centre1.y,
            bgImageWidth, bgImageHeight);

        svgEl.childNodes[4].textContent = this.labelmiddle;
        let middlex = Math.abs((this.centre1.x + this.centre2.x) / 2);
        let middley = Math.abs((this.centre1.y + this.centre2.y) / 2);
        this.adjustTextPosition(svgEl.childNodes[4], parseInt(middlex), parseInt(middley), bgImageWidth, bgImageHeight);

        svgEl.childNodes[5].textContent = this.labelend;
        this.adjustTextPosition(svgEl.childNodes[5], this.centre2.x, this.centre2.y,
            bgImageWidth, bgImageHeight);
    };

    /**
     * Update svg line attributes.
     *
     * @param {SVGElement} [svgTextEl] the text node of the SVG.
     * @param {int} [linex] coordinate of the line.
     * @param {int} [liney] coordinate of the line.
     * @param {int} bgImageWidth
     * @param {int} bgImageHeight
     */
    Line.prototype.adjustTextPosition = function(svgTextEl, linex, liney, bgImageWidth, bgImageHeight) {
        const padding = 20;

        // Text element dimensions.
        const bbox = svgTextEl.getBBox();
        const textWidth = bbox.width;

        svgTextEl.setAttribute('x', linex);
        svgTextEl.setAttribute('y', liney + padding);

        // Recalculate the position of x and y coordinates of text, to make sure the text content is fully displayed.
        if (linex < textWidth / 2) {
            svgTextEl.setAttribute('x', Math.abs(parseInt(textWidth / 2)));
        } else if ((linex + (textWidth / 2)) > bgImageWidth) {
            svgTextEl.setAttribute('x', Math.abs(parseInt(bgImageWidth - (textWidth / 2))));
        }

        if (liney + padding > bgImageHeight) {
            // Adjust if the line is very near to the bottom of the svg.
            svgTextEl.setAttribute('y', liney - padding);
        }
    };

    /**
     * Update svg line attributes.
     *
     * @param {SVGElement} svgEl the SVG representation of the shape.
     * @param {int} bgImageWidth
     * @param {int} bgImageHeight
     */
    Line.prototype.drawLine = function(svgEl, bgImageWidth, bgImageHeight) {
        // Set attributes for the polyline.
        svgEl.childNodes[0].style.stroke = "#000973";
        svgEl.childNodes[0].style['stroke-width'] = "3";
        svgEl.childNodes[0].style['stroke-dasharray'] = "10,3";

        var points = this.centre1.x + "," + this.centre1.y + " " + this.centre2.x + "," + this.centre2.y;
        svgEl.childNodes[0].setAttribute('points', points);

        // Set attributes to display line based on linetype.
        switch (this.lineType) {
            case 'linesinglearrow':
                svgEl.childNodes[0].style['marker-end'] = "url(#arrow)";
                svgEl.childNodes[0].setAttribute('class', 'shape singlearrow');
                break;

            case 'linedoublearrows':
                svgEl.childNodes[0].style['marker-start'] = "url(#arrow)";
                svgEl.childNodes[0].style['marker-end'] = "url(#arrow)";
                svgEl.childNodes[0].setAttribute('class', 'shape doublearrows');
                break;

            case 'lineinfinite':
                var newCoordinates = this.drawInfiniteLine(svgEl.parentNode, bgImageWidth, bgImageHeight);
                var infiniteLine = newCoordinates[0] + "," + newCoordinates[1] +
                    " " + points + " " + newCoordinates[2] + "," + newCoordinates[3];
                svgEl.childNodes[0].setAttribute('points', infiniteLine);
                svgEl.childNodes[0].setAttribute('class', 'shape infinite');
                break;
        }
    };

    /**
     * Get the minimum and maximum endpoints of the line to draw an infinite line.
     *
     * @param {SVGElement} svg the SVG representation of the shape.
     * @param {int} bgImageWidth
     * @param {int} bgImageHeight
     */
    Line.prototype.drawInfiniteLine = function(svg, bgImageWidth, bgImageHeight) {

        // Calculate slope
        const dx = this.centre2.x - this.centre1.x;
        const dy = this.centre2.y - this.centre1.y;

        // Calculate points far outside the SVG canvas
        let xMin, yMin, xMax, yMax;
        if (dx === 0) { // Vertical line
            xMin = xMax = this.centre1.x;
            yMin = 0;
            yMax = bgImageHeight;
        } else if (dy === 0) { // Horizontal line
            xMin = 0;
            xMax = bgImageWidth;
            yMin = yMax = this.centre1.y;
        } else {
            const slope = dy / dx;
            const intercept = this.centre1.y - slope * this.centre1.x;

            // Find intersection points with SVG canvas borders
            xMin = -bgImageWidth; // Starting far left
            yMin = slope * xMin + intercept;

            xMax = 2 * bgImageWidth; // Extending far right
            yMax = slope * xMax + intercept;

            // Clamp to canvas height bounds
            if (yMin < 0) {
                yMin = 0;
                xMin = (yMin - intercept) / slope;
            } else if (yMin > bgImageHeight) {
                yMin = bgImageHeight;
                xMin = (yMin - intercept) / slope;
            }

            if (yMax < 0) {
                yMax = 0;
                xMax = (yMax - intercept) / slope;
            } else if (yMax > bgImageHeight) {
                yMax = bgImageHeight;
                xMax = (yMax - intercept) / slope;
            }
        }
        return [Math.round(xMin), Math.round(yMin), Math.round(xMax), Math.round(yMax)];
    };

    /**
     * Parse the coordinates from the string representation.
     *
     * @param {String} startcoordinates "x1,y1;radius".
     * @param {String} endcoordinates "x1,y1;radius".
     * @param {float} ratio .
     * @return {boolean} True if the coordinates are valid and parsed. Throws an exception if input point is not valid.
     */
    Line.prototype.parse = function(startcoordinates, endcoordinates, ratio) {
        var startcoordinatesbits = startcoordinates.split(';');
        var endcoordinatesbits = endcoordinates.split(';');
        this.centre1 = Point.parse(startcoordinatesbits[0]);
        this.centre2 = Point.parse(endcoordinatesbits[0]);
        this.centre1.x = this.centre1.x * parseFloat(ratio);
        this.centre1.y = this.centre1.y * parseFloat(ratio);
        this.x1 = this.centre1.x * parseFloat(ratio);
        this.y1 = this.centre1.y * parseFloat(ratio);
        this.x2 = this.centre2.x * parseFloat(ratio);
        this.y2 = this.centre2.y * parseFloat(ratio);
        this.centre2.x = this.centre2.x * parseFloat(ratio);
        this.centre2.y = this.centre2.y * parseFloat(ratio);
        this.startRadius = Math.round(startcoordinatesbits[1]) * parseFloat(ratio);
        this.endRadius = Math.round(endcoordinatesbits[1]) * parseFloat(ratio);

        return true;
    };

    /**
     * Move the entire shape by this offset.
     *
     * @param {String} whichHandle which circle handle was moved, i.e., startcircle or endcircle.
     * @param {int} dx x offset.
     * @param {int} dy y offset.
     * @param {int} maxX ensure that after editing, the shape lies between 0 and maxX on the x-axis.
     * @param {int} maxY ensure that after editing, the shape lies between 0 and maxX on the y-axis.
     */
    Line.prototype.move = function(whichHandle, dx, dy, maxX, maxY) {
        if (whichHandle === 'startcircle') {
            this.centre1.move(dx, dy);
            if (this.centre1.x < this.startRadius) {
                this.centre1.x = this.startRadius;
                this.x1 = this.startRadius;
            }
            if (this.centre1.x > maxX - this.startRadius) {
                this.centre1.x = maxX - this.startRadius;
                this.x1 = maxX - this.startRadius;
            }
            if (this.centre1.y < this.startRadius) {
                this.centre1.y = this.startRadius;
                this.y1 = this.startRadius;
            }
            if (this.centre1.y > maxY - this.startRadius) {
                this.centre1.y = maxY - this.startRadius;
                this.y1 = maxY - this.startRadius;
            }
        } else {
            this.centre2.move(dx, dy);
            if (this.centre2.x < this.endRadius) {
                this.centre2.x = this.endRadius;
                this.x2 = this.endRadius;
            }
            if (this.centre2.x > maxX - this.endRadius) {
                this.centre2.x = maxX - this.endRadius;
                this.x2 = maxX - this.endRadius;
            }
            if (this.centre2.y < this.endRadius) {
                this.centre2.y = this.endRadius;
                this.y2 = this.endRadius;
            }
            if (this.centre2.y > maxY - this.endRadius) {
                this.centre2.y = maxY - this.endRadius;
                this.y2 = maxY - this.endRadius;
            }
        }
    };

    /**
     * Move the line end points by this offset.
     *
     * @param {int} dx x offset.
     * @param {int} dy y offset.
     * @param {int} maxX ensure that after editing, the shape lies between 0 and maxX on the x-axis.
     * @param {int} maxY ensure that after editing, the shape lies between 0 and maxX on the y-axis.
     */
    Line.prototype.moveDrags = function(dx, dy, maxX, maxY) {
        // Move the lines in the dropzones.
        this.centre1.move(dx, dy);
        this.centre2.move(dx, dy);
        if (this.centre1.x < this.startRadius) {
            this.centre1.x = this.startRadius;
            this.x1 = this.startRadius;
        }
        if (this.centre1.x > maxX - this.startRadius) {
            this.centre1.x = maxX - this.startRadius;
            this.x1 = maxX - this.startRadius;
        }
        if (this.centre2.x < this.endRadius) {
            this.centre2.x = this.endRadius;
            this.x2 = this.endRadius;
        }
        if (this.centre2.x > maxX - this.endRadius) {
            this.centre2.x = maxX - this.endRadius;
            this.x2 = maxX - this.endRadius;
        }
        if (this.centre1.y < this.startRadius) {
            this.centre1.y = this.startRadius;
            this.y1 = this.startRadius;
        }
        if (this.centre1.y > maxY - this.startRadius) {
            this.centre1.y = maxY - this.startRadius;
            this.y1 = maxY - this.startRadius;
        }
        if (this.centre2.y < this.endRadius) {
            this.centre2.y = this.endRadius;
            this.y2 = this.endRadius;
        }
        if (this.centre2.y > maxY - this.endRadius) {
            this.centre2.y = maxY - this.endRadius;
            this.y2 = maxY - this.endRadius;
        }
    };

    /**
     * Move the g element between the dropzones and dragHomes.
     *
     * @param {String} eventType Whether it's a mouse event or a keyboard event.
     * @param {SVGElement} selectedElement The element selected for dragging.
     * @param {SVG} svgDropZones
     * @param {SVG} svgDragsHome
     * @param {int|null} dropX Used by mouse events to calculate the svg to which it belongs.
     * @param {int|null} dropY
     * @param {int|null} bgImageHeight height of the background image, to decide the position of where to drop the line.
     * @param {String|null} whichSVG
     */
    Line.prototype.addToDropZone = function(eventType, selectedElement, svgDropZones, svgDragsHome,
            dropX, dropY, bgImageHeight, whichSVG) {
        let dropzoneNo = selectedElement.getAttribute('data-dropzone-no'),
            classattributes,
            dropZone = false;
        const initiallinespacing = 25,
            spacingbetweenlines = 50;
        if (eventType === 'mouse') {
            dropZone = this.isInsideSVG(svgDragsHome, dropX, dropY);
        } else {
            dropZone = (whichSVG === 'DragsSVG');
        }
        if (dropZone) {
            // Append the element to the dropzone SVG.
            svgDropZones.appendChild(selectedElement);
            selectedElement.getAttribute('data-dropzone-no');

            // Set tabindex to add keyevents to the circle movehandles.
            selectedElement.childNodes[1].setAttribute('tabindex', '0');
            selectedElement.childNodes[2].setAttribute('tabindex', '0');

            // Caluculate the position of line drop.
            this.centre1.y = bgImageHeight - (2 * this.startRadius);
            this.y1 = bgImageHeight - (2 * this.startRadius);
            this.centre2.y = bgImageHeight - (2 * this.endRadius);
            this.y2 = bgImageHeight - (2 * this.endRadius);

            // Update the class attributes to 'placed' if the line is in the svgDropZone.
            classattributes = selectedElement.getAttribute('class');
            classattributes = classattributes.replace('inactive', 'placed');
            selectedElement.setAttribute('class', classattributes);
        } else {
            // Append the element to the draghomes SVG.
            svgDragsHome.appendChild(selectedElement);

            // We want to drop the lines from the top, depending on the line number.
            // Calculate the position of line drop.
            this.centre1.x = 50;
            this.centre1.y = initiallinespacing + (dropzoneNo * spacingbetweenlines);
            this.y1 = initiallinespacing + (dropzoneNo * spacingbetweenlines);
            this.centre2.x = 200;
            this.centre2.y = initiallinespacing + (dropzoneNo * spacingbetweenlines);
            this.y2 = initiallinespacing + (dropzoneNo * spacingbetweenlines);

            // Update the class attributes to 'inactive' if the line is in the svg draghome.
            classattributes = selectedElement.getAttribute('class');
            classattributes = classattributes.replace('placed', 'inactive');
            selectedElement.setAttribute('class', classattributes);
            // Set tabindex = -1, so the circle movehandles aren't focusable when in draghomes svg.
            selectedElement.childNodes[1].setAttribute('tabindex', '-1');
            selectedElement.childNodes[2].setAttribute('tabindex', '-1');
        }
    };

    /**
     * Check if the current selected element is in the svg .
     *
     * @param {SVGElement} svg Svg element containing the drags.
     * @param {int} dropX
     * @param {int} dropY
     * @return {bool}
     */
    Line.prototype.isInsideSVG = function(svg, dropX, dropY) {
        const rect = svg.getBoundingClientRect();
        return dropX >= rect.left && dropX <= rect.right && dropY >= rect.top && dropY <= rect.bottom;
    };

    /**
     * Move one of the edit handles by this offset.
     *
     * @param {String} handleIndex which handle was moved.
     * @param {int} dx x offset.
     * @param {int} dy y offset.
     * @param {int} maxX ensure that after editing, the shape lies between 0 and maxX on the x-axis.
     * @param {int} maxY ensure that after editing, the shape lies between 0 and maxX on the y-axis.
     */
    Line.prototype.edit = function(handleIndex, dx, dy, maxX, maxY) {
        var limit = 0;
        if (handleIndex === '0') {
            this.startRadius += dx;
            limit = Math.min(this.centre1.x, this.centre1.y, maxX - this.centre1.x, maxY - this.centre1.y);
            if (this.startRadius > limit) {
                this.startRadius = limit;
            }
            if (this.startRadius < -limit) {
                this.startRadius = -limit;
            }
        } else {
            this.endRadius += dx;
            limit = Math.min(this.centre2.x, this.centre2.y, maxX - this.centre2.x, maxY - this.centre2.y);
            if (this.endRadius > limit) {
                this.endRadius = limit;
            }
            if (this.endRadius < -limit) {
                this.endRadius = -limit;
            }
        }
    };

    /**
     * Get the handles that should be offered to edit this shape, or null if not appropriate.
     *
     * @return {Object[]} with properties moveHandleStart {Point}, moveHandleEnd {Point} and editHandles {Point[]}.
     */
    Line.prototype.getHandlePositions = function() {
        return {
            moveHandles: [new Point(this.centre1.x, this.centre1.y), new Point(this.centre2.x, this.centre2.y)],
            editHandles: [this.centre1.offset(this.startRadius, 0), this.centre2.offset(this.endRadius, 0)]
        };
    };

    /**
     * Update the properties of this shape after a sequence of edits.
     *
     * For example make sure the circle radius is positive, of the polygon centre is centred.
     */
    Line.prototype.normalizeShape = function() {
        this.startRadius = Math.abs(this.startRadius);
        this.endRadius = Math.abs(this.endRadius);
    };

    /**
     * Add a new arrow SVG DOM element as a child of svg.
     *
     * @param {SVGElement} svg the parent node.
     */
     function addLineArrow(svg) {
        if (svg.getElementsByTagName('defs')[0]) {
            return;
        }
        var svgdefsEl = svg.ownerDocument.createElementNS('http://www.w3.org/2000/svg', 'defs');
        var svgmarkerEl = svg.ownerDocument.createElementNS('http://www.w3.org/2000/svg', 'marker');
        svgmarkerEl.setAttribute('id', 'arrow');
        svgmarkerEl.setAttribute('viewBox', "0 0 10 10");
        svgmarkerEl.setAttribute('refX', '7');
        svgmarkerEl.setAttribute('refY', '5');
        svgmarkerEl.setAttribute('markerWidth', '4');
        svgmarkerEl.setAttribute('markerHeight', '4');
        svgmarkerEl.setAttribute('orient', 'auto-start-reverse');
        var svgPathEl = svg.ownerDocument.createElementNS('http://www.w3.org/2000/svg', 'path');
        svgPathEl.setAttribute('d', 'M 0 0 L 10 5 L 0 10 z');
        svgmarkerEl.appendChild(svgPathEl);
        svgdefsEl.appendChild(svgmarkerEl);

        svg.appendChild(svgdefsEl);
    }

    /**
     * Make a new SVG DOM element as a child of svg.
     *
     * @param {SVGElement} svg the parent node.
     * @param {String} tagName the tag name.
     * @return {SVGElement} the newly created node.
     */
    function createSvgElement(svg, tagName) {
        var svgEl = svg.ownerDocument.createElementNS('http://www.w3.org/2000/svg', tagName);
        svg.appendChild(svgEl);
        return svgEl;
    }

    /**
     * Make a group SVG DOM elements containing a polyline of the given linetype as first child,
     * two circles to mark the allowed radius for grading and text labels for the line.
     *
     * @param {SVGElement} svg the parent node.
     * @param {String} tagName the tag name.
     * @return {SVGElement} the newly created g element.
     */
    function createSvgShapeGroup(svg, tagName) {
        var svgEl = createSvgElement(svg, 'g');
        svgEl.setAttribute('tabindex', '0');
        var lineEl = createSvgElement(svgEl, tagName);
        lineEl.setAttribute('class', 'shape');
        var startcircleEl = createSvgElement(svgEl, 'circle');
        startcircleEl.setAttribute('class', 'startcircle shape');
        var endcirleEl = createSvgElement(svgEl, 'circle');
        endcirleEl.setAttribute('class', 'endcircle shape');
        createSvgElement(svgEl, 'text').setAttribute('class', 'labelstart shapeLabel');
        createSvgElement(svgEl, 'text').setAttribute('class', 'labelmiddle shapeLabel');
        createSvgElement(svgEl, 'text').setAttribute('class', 'labelend shapeLabel');
        return svgEl;
    }

    /**
     * @alias module:qtype_drawlines/drawLine
     */
    return {
        /**
         * A point, with x and y coordinates.
         *
         * @param {int} x centre X.
         * @param {int} y centre Y.
         * @constructor
         */
        Point: Point,

        /**
         * Line constructor. Class to represent the different types of drop zone shapes.
         *
         * @param {int} [x1] centre X1.
         * @param {int} [y1] centre Y1.
         * @param {int} [startRadius] startRadius.
         * @param {int} [x2] centre X2.
         * @param {int} [y2] centre Y2.
         * @param {int} [endRadius] endRadius.
         * @param {String} [lineType] Line type.
         * @param {String} [labelstart] start label of a line.
         * @param {String} [labelmiddle] middle label of a line.
         * @param {String} [labelend] end label of a line.
         * @constructor
         */
        Line: Line,

        /**
         * Make a new SVG DOM element as a child of svg.
         *
         * @param {SVGElement} svg the parent node.
         * @param {String} tagName the tag name.
         * @return {SVGElement} the newly created node.
         */
        createSvgElement: createSvgElement,

        /**
         * Make a line of the given type.
         *
         * @param {Array} [linecoordinates] in the format (x,y;radius).
         * @param {String} [lineType] The linetype (e.g., linesinglearrow, linedoublearrows, ...).
         * @param {Array} [labels] Start, middle and end labels of a line.
         * @return {Line} the new line.
         */
        make: function(linecoordinates, lineType, labels) {
            // Line coordinates are in the format (x,y;radius).
            var startcoordinates = linecoordinates[0].split(';');
            var endcoordinates = linecoordinates[1].split(';');
            var linestartbits = startcoordinates[0].split(',');
            var lineendbits = endcoordinates[0].split(',');

            return new Line(parseInt(linestartbits[0]), parseInt(linestartbits[1]), parseInt(startcoordinates[1]),
                parseInt(lineendbits[0]), parseInt(lineendbits[1]), parseInt(endcoordinates[1]), lineType,
                labels[0], labels[1], labels[2]);
        },

        /**
         * Make a line of the given linetype having similar coordinates and labels as the original type.
         *
         * @param {String} lineType the new type of line to make.
         * @param {line} line the line to copy.
         * @return {line} the similar line of a different linetype.
         */
        getSimilar: function(lineType, line) {
            return new Line(parseInt(line.x1), parseInt(line.y1), parseInt(line.startRadius),
                parseInt(line.x2), parseInt(line.y2), parseInt(line.endRadius), lineType,
                line.labelstart, line.labelmiddle, line.labelend);
        }
    };
});
