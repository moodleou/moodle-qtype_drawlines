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
 * Library of classes for handling simple shapes.
 *
 * These classes can represent shapes, let you alter them, can go to and from a string
 * representation, and can give you an SVG representation.
 *
 * @module qtype_drawlines/drawLine
 * @copyright  2018 The Open University
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
     * @returns {string} "x;y";
     */
    Point.prototype.toString = function() {
        return this.x + ',' + this.y;
    };

    /**
     * Move a point
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
     * @param {(int|Point)} offsetX if a point, offset by this points coordinates, else and int x offset.
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
     * @param {String} [labelstart] start label of a line.
     * @param {int} [x1] centre X1.
     * @param {int} [y1] centre Y1.
     * @param {int} [startRadius] startRadius.
     * @param {String} [labelend] end label of a line.
     * @param {int} [x2] centre X2.
     * @param {int} [y2] centre Y2.
     * @param {int} [endRadius] endRadius.
     * @param {String} [lineType] Line type.
     * @constructor
     */
    function Line(labelstart, x1, y1, startRadius, labelend, x2, y2, endRadius, lineType) {
        this.labelstart = labelstart;
        this.labelend = labelend;
        this.x1 = x1 || 15;
        this.y1 = y1 || 100;

        this.x2 = x2 || 200;
        this.y2 = y2 || 250;

        this.centre1 = new Point(x1 || 0, y1 || 0);
        this.centre2 = new Point(x2 || 0, y2 || 0);

        this.startRadius = startRadius;
        this.endRadius = endRadius;

        this.lineType = lineType;
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
     * @return {SVGElement} SVG representation of this shape.
     */
    Line.prototype.makeSvg = function(svg) {
        addLineArrow(svg);
        var svgEl = createSvgShapeGroup(svg, 'polyline');
        this.updateSvg(svgEl);
        return svgEl;
    };

    /**
     * Update the SVG representation of this shape.
     *
     * @param {SVGElement} svgEl the SVG representation of this shape.
     */
    Line.prototype.updateSvg = function(svgEl) {

        // Set line attributes.
        this.drawLine(svgEl);

        // Set start and end circle attributes.
        svgEl.childNodes[1].setAttribute('cx', this.centre1.x);
        svgEl.childNodes[1].setAttribute('cy', this.centre1.y);
        svgEl.childNodes[1].setAttribute('r', Math.abs(this.startRadius));

        svgEl.childNodes[2].setAttribute('cx', this.centre2.x);
        svgEl.childNodes[2].setAttribute('cy', this.centre2.y);
        svgEl.childNodes[2].setAttribute('r', Math.abs(this.endRadius));

        // Set start and end label attributes.
        svgEl.childNodes[3].textContent = this.labelstart;
        svgEl.childNodes[3].setAttribute('x', this.centre1.x);
        svgEl.childNodes[3].setAttribute('y', parseInt(this.centre1.y) + 20);

        svgEl.childNodes[4].textContent = this.labelend;
        svgEl.childNodes[4].setAttribute('x', this.centre2.x);
        svgEl.childNodes[4].setAttribute('y', parseInt(this.centre2.y) + 20);
    };

    /**
     * Update svg line attributes.
     *
     * @param {SVGElement} svgEl the SVG representation of the shape.
     */
    Line.prototype.drawLine = function(svgEl) {
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
                var newCoordinates = this.drawInfiniteLine(svgEl.parentNode);
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
     */
    Line.prototype.drawInfiniteLine = function(svg) {

        const width = svg.width.baseVal.value;
        const height = svg.height.baseVal.value;

        // Calculate slope
        const dx = this.centre2.x - this.centre1.x;
        const dy = this.centre2.y - this.centre1.y;

        // Calculate points far outside the SVG canvas
        let xMin, yMin, xMax, yMax;
        if (dx === 0) { // Vertical line
            xMin = xMax = this.centre1.x;
            yMin = 0;
            yMax = height;
        } else if (dy === 0) { // Horizontal line
            xMin = 0;
            xMax = width;
            yMin = yMax = this.centre1.y;
        } else {
            const slope = dy / dx;
            const intercept = this.centre1.y - slope * this.centre1.x;

            // Find intersection points with SVG canvas borders
            xMin = -width; // Starting far left
            yMin = slope * xMin + intercept;

            xMax = 2 * width; // Extending far right
            yMax = slope * xMax + intercept;

            // Clamp to canvas height bounds
            if (yMin < 0) {
                yMin = 0;
                xMin = (yMin - intercept) / slope;
            } else if (yMin > height) {
                yMin = height;
                xMin = (yMin - intercept) / slope;
            }

            if (yMax < 0) {
                yMax = 0;
                xMax = (yMax - intercept) / slope;
            } else if (yMax > height) {
                yMax = height;
                xMax = (yMax - intercept) / slope;
            }
        }
        return [Math.round(xMin), Math.round(yMin), Math.round(xMax), Math.round(yMax)];

    };

    /**
     * Parse the coordinates from the string representation.
     *
     * @param {String} startcoordinates "x1,y1".
     * @param {String} endcoordinates "x1,y1".
     * @param {float} ratio .
     * @return {Point} the point. Throws an exception if input is not valid.
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
     * @param {String} handleIndex which handle was moved.
     * @param {int} dx x offset.
     * @param {int} dy y offset.
     * @param {int} maxX ensure that after editing, the shape lies between 0 and maxX on the x-axis.
     * @param {int} maxY ensure that after editing, the shape lies between 0 and maxX on the y-axis.
     */
    Line.prototype.move = function(handleIndex, dx, dy, maxX, maxY) {
        if (handleIndex === '0') {
            this.centre1.move(dx, dy);
            if (this.centre1.x < this.startRadius) {
                this.centre1.x = this.startRadius;
                this.x1 = this.startRadius;
            }
            if (this.centre1.x > maxX - this.startRadius) {
                this.centre1.x = maxX - this.startRadius;
                this.x1 = maxX - this.startRadius;
            }
            if (this.centre1.y < this.endRadius) {
                this.centre1.y = this.endRadius;
                this.y1 = this.endRadius;
            }
            if (this.centre1.y > maxY - this.endRadius) {
                this.centre1.y = maxY - this.endRadius;
                this.y1 = maxY - this.endRadius;
            }
        } else {
            this.centre2.move(dx, dy);
            if (this.centre2.x < this.startRadius) {
                this.centre2.x = this.startRadius;
                this.x2 = this.startRadius;
            }
            if (this.centre2.x > maxX - this.startRadius) {
                this.centre2.x = maxX - this.startRadius;
                this.x2 = maxX - this.startRadius;
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
     * Move the entire line by this offset.
     *
     * @param {int} dx x offset.
     * @param {int} dy y offset.
     * @param {int} maxX ensure that after editing, the shape lies between 0 and maxX on the x-axis.
     * @param {int} maxY ensure that after editing, the shape lies between 0 and maxX on the y-axis.
     * @param {String} whichSVG The svg containing the drag.
     */
    Line.prototype.moveDrags = function(dx, dy, maxX, maxY, whichSVG) {
        // If the drags are in the dragHomes then we want to keep the x coordinates fixed.
        if (whichSVG === 'DragsSVG') {
            // We don't want to move drags horizontally in this SVG.
            this.centre1.move(0, dy);
            this.centre2.move(0, dy);
            this.centre1.x = 50;
            this.x1 = 50;
            this.centre2.x = 200;
            this.x2 = 200;
        } else {
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
            if (this.centre2.x < this.startRadius) {
                this.centre2.x = this.startRadius;
                this.x2 = this.startRadius;
            }
            if (this.centre2.x > maxX - this.startRadius) {
                this.centre2.x = maxX - this.startRadius;
                this.x2 = maxX - this.startRadius;
            }
        }
        if (this.centre1.y < this.endRadius) {
            this.centre1.y = this.endRadius;
            this.y1 = this.endRadius;
        }
        if (this.centre1.y > maxY - this.endRadius) {
            this.centre1.y = maxY - this.endRadius;
            this.y1 = maxY - this.endRadius;
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
     * @param {SVGElement} svgDragsHome Svg element containing the drags.
     * @param {SVGElement} svgDropZones Svg element containing the dropZone.
     * @param {SVGElement} selectedElement The element selected for dragging.
     * @param {int} dropX
     * @param {int} dropY
     */
    Line.prototype.addToDropZone = function(svgDragsHome, svgDropZones, selectedElement, dropX, dropY) {
        var maxY = 0;
        var dropzoneNo = selectedElement.getAttribute('data-dropzone-no');
        var classattributes = '';
        if (this.isInsideSVG(svgDragsHome, dropX, dropY)) {
            // Append the element to the second SVG
            // Get the height of the dropZone SVG.
            maxY = svgDropZones.height.baseVal.value;
            svgDropZones.appendChild(selectedElement);
            selectedElement.getAttribute('data-dropzone-no');

            // Caluculate the position of line drop.
            // this.centre1.y = maxY - (2 * this.startRadius) - (dropzoneNo * 50);
            // this.y1 = maxY - (2 * this.startRadius) - (dropzoneNo * 50);
            // this.centre2.y = maxY - (2 * this.endRadius) - (dropzoneNo * 50);
            // this.y2 = maxY - (2 * this.endRadius) - (dropzoneNo * 50);
            this.centre1.y = maxY - (2 * this.startRadius);
            this.y1 = maxY - (2 * this.startRadius);
            this.centre2.y = maxY - (2 * this.endRadius);
            this.y2 = maxY - (2 * this.endRadius);

            // Update the class attributes to 'placed' if the line is in the svgDropZone.
            classattributes = selectedElement.getAttribute('class');
            classattributes = classattributes.replace('inactive', 'placed');
            selectedElement.setAttribute('class', classattributes);

        } else if (this.isInsideSVG(svgDropZones, dropX, dropY)) {
            // Append the element to the first SVG (to ensure it stays in the same SVG if dropped there)
            svgDragsHome.appendChild(selectedElement);

            // We want to drop the lines from the top, depending on the line number.
            // Calculate the position of line drop.
            this.centre1.x = 50;
            this.centre1.y = this.startRadius + (dropzoneNo * 50);
            this.y1 = this.startRadius + (dropzoneNo * 50);
            this.centre2.x = 200;
            this.centre2.y = this.endRadius + (dropzoneNo * 50);
            this.y2 = this.endRadius + (dropzoneNo * 50);

            // Update the class attributes to 'inactive' if the line is in the svg draghome.
            classattributes = selectedElement.getAttribute('class');
            classattributes = classattributes.replace('placed', 'inactive');
            selectedElement.setAttribute('class', classattributes);
        }
        return '';
    };

    /**
     * Check if the current selected element is in the svg .
     * @param {SVGElement} svg Svg element containing the drags.
     * @param {int} dropX
     * @param {int} dropY
     * @return {bool}
     */
    Line.prototype.isInsideSVG = function(svg, dropX, dropY){
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
     * @return {SVGElement} the newly created node.
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
        createSvgElement(svgEl, tagName).setAttribute('class', 'shape');
        createSvgElement(svgEl, 'circle').setAttribute('class', 'startcircle shape');
        createSvgElement(svgEl, 'circle').setAttribute('class', 'endcircle shape');
        createSvgElement(svgEl, 'text').setAttribute('class', 'labelstart shapeLabel');
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
         * @param {String} [labelstart] start label of a line.
         * @param {int} [x1] centre X1.
         * @param {int} [y1] centre Y1.
         * @param {int} [startRadius] startRadius.
         * @param {String} [labelend] end label of a line.
         * @param {int} [x2] centre X2.
         * @param {int} [y2] centre Y2.
         * @param {int} [endRadius] endRadius.
         * @param {String} [lineType] Line type.
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
         * @param {Array} linecoordinates in the format (x,y;radius).
         * @param {Array} labels Start and end labels of a line.
         * @param {String} lineType The linetype (e.g., linesinglearrow, linedoublearrows, ...).
         * @return {Line} the new line.
         */
        make: function(linecoordinates, labels, lineType) {
            // Line coordinates are in the format (x,y;radius).
            var startcoordinates = linecoordinates[0].split(';');
            var endcoordinates = linecoordinates[1].split(';');
            var linestartbits = startcoordinates[0].split(',');
            var lineendbits = endcoordinates[0].split(',');

            return new Line(labels[0], linestartbits[0], linestartbits[1], startcoordinates[1], labels[1],
                lineendbits[0], lineendbits[1], endcoordinates[1], lineType);
        },

        /**
         * Make a line of the given linetype having similar coordinates and labels as the original type.
         *
         * @param {String} lineType the new type of line to make.
         * @param {line} line the line to copy.
         * @return {line} the similar line of a different linetype.
         */
        getSimilar: function(lineType, line) {
            return new Line(line.labelstart, parseInt(line.x1), parseInt(line.y1), parseInt(line.startRadius),
                parseInt(line.labelend), parseInt(line.x2), parseInt(line.y2), parseInt(line.endRadius), lineType);
        }
    };
});
