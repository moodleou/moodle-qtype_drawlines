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

/**
 * JavaScript to allow dragging options for lines (using mouse down or touch) or tab through lines using keyboard.
 *
 * @module     qtype_drawlines/question
 * @copyright  2024 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'core/dragdrop',
    'qtype_drawlines/line',
    'core/key_codes',
    'core_form/changechecker',
], function(
    $,
    dragDrop,
    Line,
) {

    "use strict";

    /**
     * Object to handle one drag-drop markers question.
     *
     * @param {String} containerId id of the outer div for this question.
     * @param {boolean} readOnly whether the question is being displayed read-only.
     * @param {Object[]} visibleDropZones the geometry of any drop-zones to show.
     *      Objects have fields line, coords and markertext.
     * @param {line[]} questionLines
     * @constructor
     */
    function DrawlinesQuestion(containerId, readOnly, visibleDropZones, questionLines) {
        var thisQ = this;
        this.containerId = containerId;
        this.visibleDropZones = visibleDropZones;
        this.questionLines = questionLines;
        M.util.js_pending('qtype_drawlines-init-' + this.containerId);
        this.lineSVGs = [];
        this.lines = [];
        this.svgEl = null;
        this.isPrinting = false;
        if (readOnly) {
            this.getRoot().classList.add('qtype_drawlines-readonly');
        }
        let bgImage = this.bgImage();
        thisQ.createSvgOnImageLoad(bgImage);
    }

    /**
     * Update the coordinates from a particular string.
     */
    DrawlinesQuestion.prototype.updateCoordinates = function() {
        // We don't need to scale the shape for editing form.
        for (var line = 0; line < this.lineSVGs.length; line++) {
            var coordinates = this.getSVGLineCoordinates(this.lineSVGs[line]);
            if (!this.lines[line].parse(coordinates[0], coordinates[1], 1)) {
                // Invalid coordinates. Don't update the preview.
                return;
            }
            this.updateSvgEl(line);
        }
    };

    /**
     * Parse the coordinates from a particular string.
     *
     * @param {String} coordinates The coordinates to be parsed. The values are in the format: x1,y1 x2,y2.
     *                             Except for infinite line type where it's in the format x1,y1 x2,y2, x3,y3, x4,y4.
     *                             Here, x1,y1 and x4,y4 are the two very end points of the infinite line and
     *                             x2,y2 and x3,y3 are the pints with the handles.
     * @param {String} lineType The type of the line.
     */
    DrawlinesQuestion.prototype.parseCoordinates = function(coordinates, lineType) {
        var bits = coordinates.split(' ');
        if (lineType === 'lineinfinite' && bits.length !== 2) {
            // Remove the first and last coordinates.
            bits = bits.slice(1, -1);
        }
        if (bits.length !== 2) {
            throw new Error(coordinates + ' is not a valid point');
        }
        return bits;
    };

    /**
     * Draws the svg lines of any drop zones that should be visible for feedback purposes.
     */
    DrawlinesQuestion.prototype.drawDropzone = function() {
        let rootElement = this.getRoot(),
            bgImage = this.bgImage(),
            svg = rootElement.querySelector('svg.dropzones');
        rootElement.querySelector('.que-dlines-dropzone').style.position = 'relative';
        rootElement.querySelector('.que-dlines-dropzone').style.top = (bgImage.height + 1) * -1 + "px";
        rootElement.querySelector('.que-dlines-dropzone').style.height = bgImage.height + "px";
        rootElement.querySelector('.droparea').style.height = bgImage.height + "px";
        if (!svg) {
            let dropZone = rootElement.querySelector('.que-dlines-dropzone');
            dropZone.innerHTML =
                '<svg xmlns="http://www.w3.org/2000/svg" ' +
                    'class= "dropzones" ' +
                    'width="' + bgImage.width + '" ' +
                    'height="' + bgImage.height + '" ' +
                    'viewBox="0 0 ' + bgImage.width + ' ' + bgImage.height + '" ' +
                    'preserveAspectRatio="xMinYMin meet" ' +
                '></svg>';
            this.drawSVGLines(this.questionLines);
        }
    };

    /**
     * Draws the svg lines of any drop zones.
     *
     * @param {Object[]} questionLines
     */
    DrawlinesQuestion.prototype.drawSVGLines = function(questionLines) {
        let bgImage = this.bgImage(),
            rootElement = this.getRoot(),
            height, startcoordinates, endcoordinates, draginitialcoords;

        let drags = rootElement.querySelector('.draghomes');
        drags.innerHTML =
            '<svg xmlns="http://www.w3.org/2000/svg" class="dragshome" ' +
            'width="' + bgImage.width + '" ' +
            'height="' + questionLines.length * 50 + '"' +
            '></svg>';

        let draghomeSvg = rootElement.querySelector('.dragshome'),
            dropzoneSvg = rootElement.querySelector('.dropzones');
        const initiallinespacing = 25,
            spacingbetweenlines = 50;
        for (let line = 0; line < this.questionLines.length; line++) {
            height = initiallinespacing + (line * spacingbetweenlines);
            startcoordinates = '50,' + height + ';10';
            endcoordinates = '200,' + height + ';10';

            // Check if the lines are to be set with initial coordinates.
            draginitialcoords = this.visibleDropZones['c' + line];
            if (draginitialcoords !== undefined && draginitialcoords !== '') {
                // The visibleDropZones array holds the response in the format x1,y1 x2,y2 - to be added to svgdropzone.
                var coords = this.parseCoordinates(draginitialcoords, questionLines[line].type);
                startcoordinates = coords[0] + ';10';
                endcoordinates = coords[1] + ';10';
                this.lines[line] = Line.make(
                    [startcoordinates, endcoordinates],
                    questionLines[line].type,
                    [questionLines[line].labelstart, questionLines[line].labelmiddle, questionLines[line].labelend]
                );
                this.addToSvg(line, dropzoneSvg);
            } else {
                // Need to be added to draghomeSvg.
                this.lines[line] = Line.make(
                    [startcoordinates, endcoordinates],
                    questionLines[line].type,
                    [questionLines[line].labelstart, questionLines[line].labelmiddle, questionLines[line].labelend]
                );
                this.addToSvg(line, draghomeSvg);
            }
        }
        M.util.js_complete('qtype_drawlines-init-' + this.containerId);
    };

    /**
     * Handle when the window is resized.
     */
    DrawlinesQuestion.prototype.handleResize = function() {
        let thisQ = this,
            bgImg = this.bgImage(),
            bgRatio = this.bgRatio(),
            svgdropzones,
            svgdraghomes;

        // Calculate and set the svg attributes.
        // We need to call drawDropzone function to make sure the svg's are created before updating the attributes.
        thisQ.drawDropzone();
        svgdropzones = this.getRoot().querySelector('div.droparea svg.dropzones');
        svgdraghomes = this.getRoot().querySelector('div.draghomes svg.dragshome');
        svgdropzones.setAttribute("width", bgImg.width);
        svgdropzones.setAttribute("height", bgImg.height);
        svgdropzones.setAttribute("viewBox", '0 0 ' + bgImg.width + ' ' + bgImg.height);

        svgdraghomes.setAttribute("width", bgImg.width);
        svgdraghomes.setAttribute("height", parseInt(thisQ.questionLines.length * 50 * bgRatio));

        // Transform the svg lines to scale based on window size.
        for (let linenumber = 0; linenumber < thisQ.questionLines.length; linenumber++) {
            var svgline = thisQ.getRoot().querySelector('.dropzone.choice' + linenumber);
            thisQ.handleElementScale(svgline);
        }
    };

    /**
     * Return the background ratio.
     *
     * @returns {number} Background ratio.
     */
    DrawlinesQuestion.prototype.bgRatio = function() {
        var bgImg = this.bgImage();
        var bgImgNaturalWidth = bgImg.naturalWidth;
        var bgImgClientWidth = bgImg.width;
        // Sometimes the width is returned 0, when image is not loaded properly.
        if (bgImgClientWidth === 0) {
            return 1;
        }
        return bgImgClientWidth / bgImgNaturalWidth;
    };

    /**
     * Scale the drag if needed.
     *
     * @param {SVGElement} element the line to place.
     */
    DrawlinesQuestion.prototype.handleElementScale = function(element) {
        var bgRatio = this.bgRatio();
        if (this.isPrinting) {
            bgRatio = 1;
        }
        element.setAttribute('transform', 'scale(' + bgRatio + ')');
    };

    /**
     * Get the outer div for this question.
     *
     * @return {*}
     */
    DrawlinesQuestion.prototype.getRoot = function() {
        return document.getElementById(this.containerId);
    };

    /**
     * Get the img that is the background image.
     *
     * @returns {element|undefined} the DOM element (if any)
     */
    DrawlinesQuestion.prototype.bgImage = function() {
        return this.getRoot().querySelector('img.dropbackground');
    };

    /**
     * Returns the coordinates for the line from the SVG.
     * @param {SVGElement} svgEl
     * @returns {Array} the coordinates.
     */
    DrawlinesQuestion.prototype.getSVGLineCoordinates = function(svgEl) {

        var circleStartXCoords = svgEl.childNodes[1].getAttribute('cx');
        var circleStartYCoords = svgEl.childNodes[1].getAttribute('cy');
        var circleStartRCoords = svgEl.childNodes[1].getAttribute('r');
        var circleEndXCoords = svgEl.childNodes[2].getAttribute('cx');
        var circleEndYCoords = svgEl.childNodes[2].getAttribute('cy');
        var circleEndRCoords = svgEl.childNodes[2].getAttribute('r');
        return [circleStartXCoords + ',' + circleStartYCoords + ';' + circleStartRCoords,
            circleEndXCoords + ',' + circleEndYCoords + ';' + circleEndRCoords];
    };

    /**
     * Add this line to an SVG graphic.
     *
     * @param {int} lineNumber Line Number
     * @param {SVGElement} svg the SVG image to which to add this drop zone.
     */
    DrawlinesQuestion.prototype.addToSvg = function(lineNumber, svg) {
        let bgImage = this.bgImage();
        this.lineSVGs[lineNumber] = this.lines[lineNumber].makeSvg(svg, bgImage.naturalWidth,
            bgImage.naturalHeight);
        if (!this.lineSVGs[lineNumber]) {
            return;
        }
        this.lineSVGs[lineNumber].setAttribute('data-dropzone-no', lineNumber);
        if (svg.getAttribute('class') === 'dropzones') {
            this.lineSVGs[lineNumber].setAttribute('class', 'dropzone choice' + lineNumber + ' placed');
        } else {
            this.lineSVGs[lineNumber].setAttribute('class', 'dropzone choice' + lineNumber + ' inactive');
        }
    };

    /**
     * Update the line of this drop zone in an SVG image.
     *
     * @param {int} dropzoneNo
     */
    DrawlinesQuestion.prototype.updateSvgEl = function(dropzoneNo) {
        var bgimage = this.bgImage();
        this.lines[dropzoneNo].updateSvg(this.lineSVGs[dropzoneNo], bgimage.naturalWidth, bgimage.naturalHeight);
    };

    /**
     * Start responding to dragging the move handle attached to the line ends (circles).
     *
     * @param {Event} e Event object
     * @param {String} whichHandle which circle handle was moved, i.e., startcircle or endcircle.
     * @param {int} dropzoneNo
     */
    DrawlinesQuestion.prototype.handleCircleMove = function(e, whichHandle, dropzoneNo) {
        var info = dragDrop.prepare(e);
        if (!info.start) {
            return;
        }
        var movingDropZone = this,
            lastX = info.x,
            lastY = info.y,
            dragProxy = this.makeDragProxy(info.x, info.y),
            bgimage = this.bgImage(),
            maxX = bgimage.naturalWidth,
            maxY = bgimage.naturalHeight;

        dragDrop.start(e, $(dragProxy), function(pageX, pageY) {
            movingDropZone.lines[dropzoneNo].move(whichHandle,
                parseInt(pageX) - parseInt(lastX), parseInt(pageY) - parseInt(lastY), parseInt(maxX), parseInt(maxY));
            lastX = pageX;
            lastY = pageY;
            movingDropZone.updateSvgEl(dropzoneNo);
            movingDropZone.saveCoordsForChoice(dropzoneNo);
        }, function() {
            document.body.removeChild(dragProxy);
        });
    };

    /**
     * Start responding to dragging the move handle attached to the line.
     *
     * @param {Event} e Event object
     * @param {int} dropzoneNo
     */
    DrawlinesQuestion.prototype.handleLineMove = function(e, dropzoneNo) {
        var info = dragDrop.prepare(e);
        if (!info.start) {
            return;
        }
        var movingDrag = this,
            lastX = info.x,
            lastY = info.y,
            dragProxy = this.makeDragProxy(info.x, info.y),
            maxX,
            maxY,
            whichSVG = "",
            bgImage = this.bgImage(),
            isMoveFromDragsToDropzones,
            isMoveFromDropzonesToDrags,
            svgClass;

        var selectedElement = this.lineSVGs[dropzoneNo];

        let dropX, dropY;
        if (e.type === 'mousedown') {
            dropX = e.clientX;
            dropY = e.clientY;
        } else if (e.type === 'touchstart') {
            dropX = e.touches[0].clientX;
            dropY = e.touches[0].clientY;
        }
        dragDrop.start(e, $(dragProxy), function(pageX, pageY) {

            // The svg's which are associated with this question.
            var closestSVGs = movingDrag.getSvgsClosestToElement(selectedElement);

            // Check if the drags need to be moved from one svg to another.
            var closeTo = selectedElement.closest('svg');
            svgClass = closeTo.getAttribute('class');

            // Moving the drags between the SVG's.
            // If true, the drag is moved from draghomes SVG to dropZone SVG.
            isMoveFromDragsToDropzones = (svgClass === "dragshome");

            // If true, the drag is moved from dropZone SVG to draghomes SVG.
            isMoveFromDropzonesToDrags = (svgClass === 'dropzones') &&
                (movingDrag.lines[dropzoneNo].centre1.y > (bgImage.naturalHeight - 20));

            if (isMoveFromDragsToDropzones || isMoveFromDropzonesToDrags) {
                movingDrag.lines[dropzoneNo].addToDropZone('mouse', selectedElement,
                    closestSVGs.svgDropZone, closestSVGs.svgDragsHome, dropX, dropY, bgImage.naturalHeight);
            }

            // Drag the lines within the SVG
            // Get the dimensions of the selected element's svg.
            closeTo = selectedElement.closest('svg');
            var dimensions = movingDrag.getSvgDimensionsByClass(closeTo, closeTo.getAttribute('class'));
            maxX = dimensions.maxX;
            maxY = dimensions.maxY;
            whichSVG = dimensions.whichSVG;

            // Move the lines if they are in the dropzones svg.
            if (whichSVG === 'DropZonesSVG') {
                movingDrag.lines[dropzoneNo].moveDrags(
                    parseInt(pageX) - parseInt(lastX), parseInt(pageY) - parseInt(lastY),
                    parseInt(maxX), parseInt(maxY));
                lastX = pageX;
                lastY = pageY;
            }

            movingDrag.updateSvgEl(dropzoneNo);
            movingDrag.saveCoordsForChoice(dropzoneNo);
        }, function() {
            document.body.removeChild(dragProxy);
        });
    };

    /**
     * Make an invisible drag proxy.
     *
     * @param {int} x x position .
     * @param {int} y y position.
     * @returns {HTMLElement} the drag proxy.
     */
    DrawlinesQuestion.prototype.makeDragProxy = function(x, y) {
        var dragProxy = document.createElement('div');
        dragProxy.style.position = 'absolute';
        dragProxy.style.top = y + 'px';
        dragProxy.style.left = x + 'px';
        dragProxy.style.width = '1px';
        dragProxy.style.height = '1px';
        document.body.appendChild(dragProxy);
        return dragProxy;
    };

    /**
     * Save the coordinates for a dropped item in the form field.
     *
     * @param {Number} choiceNo which copy of the choice this was.
     **/
    DrawlinesQuestion.prototype.saveCoordsForChoice = function(choiceNo) {
        let imageCoords = [];
        var items = this.getRoot().querySelector('svg g.choice' + choiceNo),
            gEleClassAttributes = '';
        if (items) {
                imageCoords = items.querySelector('polyline').getAttribute('points');
                gEleClassAttributes = items.getAttribute('class');
        }
        if (gEleClassAttributes !== '' && gEleClassAttributes.includes('placed')) {
            this.getRoot().querySelector('input.choice' + choiceNo).value = imageCoords;
        } else if (gEleClassAttributes !== '' && gEleClassAttributes.includes('inactive')) {
            this.getRoot().querySelector('input.choice' + choiceNo).value = '';
        }
    };

    /**
     * Handle key down / press events on svg lines.
     *
     * @param {KeyboardEvent} e
     * @param {SVGElement} drag SVG element being dragged.
     * @param {int} dropzoneNo
     * @param {String} activeElement The element being dragged, whether it is the line or the line endpoints.
     */
    DrawlinesQuestion.prototype.handleKeyPress = function(e, drag, dropzoneNo, activeElement) {

        var x = 0,
            y = 0,
            dropzoneElement,
            question = questionManager.getQuestionForEvent(e);

        dropzoneElement = drag.closest('g');
        switch (e.code) {
            case 'ArrowLeft':
            case 'KeyA': // A.
                x = -1;
                break;
            case 'ArrowRight':
            case 'KeyD': // D.
                x = 1;
                break;
            case 'ArrowDown':
            case 'KeyS': // S.
                y = 1;
                break;
            case 'ArrowUp':
            case 'KeyW': // W.
                y = -1;
                break;
            case 'Space':
            case 'Escape':
                break;
            default:
                return; // Ingore other keys.
        }
        e.preventDefault();

        // Moving the drags between the SVG's.
        var closeTo = drag.closest('svg');
        var svgClass = closeTo.getAttribute('class');
        var maxX,
            maxY,
            whichSVG;
        var bgImage = this.bgImage();
        var closestSVGs = this.getSvgsClosestToElement(drag);
        var isMoveFromDragsToDropzones = (svgClass === "dragshome");
        var isMoveFromDropzonesToDrags = (svgClass === 'dropzones') &&
            (question.lines[dropzoneNo].centre1.y > ((bgImage.naturalHeight - 20)));

        if (isMoveFromDragsToDropzones) {
            question.lines[dropzoneNo].addToDropZone('keyboard', dropzoneElement,
                closestSVGs.svgDropZone, closestSVGs.svgDragsHome, null, null, bgImage.naturalHeight, 'DragsSVG');
        } else if (isMoveFromDropzonesToDrags) {
            question.lines[dropzoneNo].addToDropZone('keyboard', dropzoneElement,
                closestSVGs.svgDropZone, closestSVGs.svgDragsHome, null, null, null, 'DropZonesSVG');
        }

        // Get the dimensions of the selected element's svg.
        closeTo = drag.closest('svg');
        var dimensions = question.getSvgDimensionsByClass(closeTo, closeTo.getAttribute('class'));
        maxX = dimensions.maxX;
        maxY = dimensions.maxY;
        whichSVG = dimensions.whichSVG;

        if (activeElement === 'line' && whichSVG === 'DropZonesSVG') {
            // Move the entire line when the focus is on it.
            question.lines[dropzoneNo].moveDrags(parseInt(x), parseInt(y), parseInt(maxX), parseInt(maxY));
        } else {
            // Move the line endpoints.
            question.lines[dropzoneNo].move(activeElement, parseInt(x), parseInt(y), parseInt(maxX), parseInt(maxY));
        }
        question.updateSvgEl(dropzoneNo);
        this.saveCoordsForChoice(dropzoneNo);
        drag.focus();
    };

    /**
     * Returns the dimensions of the SVG image to which the drag element belongs.
     *
     * @param {SVG} dragSVG The SVG to which the drag element belongs.
     * @param {String} className Class asscociated with the SVG
     * @return {{whichSVG: (string), maxY: number, maxX: number}}
     */
    DrawlinesQuestion.prototype.getSvgDimensionsByClass = function(dragSVG, className) {
        let bgImg = this.bgImage();
        return {
            maxX: bgImg.naturalWidth,
            maxY: bgImg.naturalHeight,
            whichSVG: className === 'dragshome' ? 'DragsSVG' : 'DropZonesSVG'
        };
    };

    /**
     * Returns the SVG's to which the drag element belongs.
     *
     * @param {SVGElement} dragElement The element which is being moved.
     * @return {{svgDragsHome, svgDropZone}}
     */
    DrawlinesQuestion.prototype.getSvgsClosestToElement = function(dragElement) {
        var svgElement = dragElement.closest('svg');
        var svgElementClass = svgElement.getAttribute('class');
        var svgDragsHome, svgDropZone, parent;

        if (svgElementClass === "dragshome") {
            svgDragsHome = svgElement;
            parent = svgElement.closest('.ddarea');
            svgDropZone = parent.querySelector('.dropzones');
        } else {
            svgDropZone = svgElement;
            parent = svgElement.closest('.ddarea');
            svgDragsHome = parent.querySelector('.dragshome');
        }
        return {
            svgDropZone: svgDropZone,
            svgDragsHome: svgDragsHome
        };
    };

    /**
     * Loading SVG image.
     *
     * @param {HTMLImageElement}  img
     */
    DrawlinesQuestion.prototype.createSvgOnImageLoad = function(img) {
        if (!img) {
            window.console.error(`Image with id '${img}' not found.`);
            return;
        }

        // Check if the image is already loaded
        if (img.complete && img.naturalHeight !== 0) {
            this.drawDropzone();
        } else {
            // Add an event listener for the load event
            img.addEventListener('load', () => this.drawDropzone());
        }
    };

    /**
     * Singleton that tracks all the DrawlinesQuestions on this page, and deals
     * with event dispatching.
     *
     * @type {Object}
     */
    var questionManager = {

        /**
         * {boolean} ensures that the event handlers are only initialised once per page.
         */
        eventHandlersInitialised: false,

        /**
         * {Object} ensures that the marker event handlers are only initialised once per question,
         * indexed by containerId (id on the .que div).
         */
        lineEventHandlersInitialised: {},

        /**
         * {boolean} is printing or not.
         */
        isPrinting: false,

        /**
         * {boolean} is keyboard navigation.
         */
        isKeyboardNavigation: false,

        /**
         * {Object} all the questions on this page, indexed by containerId (id on the .que div).
         */
        questions: {}, // An object containing all the information about each question on the page.

        /**
         * @var {int} the number of lines on the form.
         */
        noOfLines: null,

        /**
         * @var {DrawlinesQuestion[]} the lines in the preview, indexed by line number.
         */
        dropZones: [],

        /**
         * @var {line[]} the question lines in the preview, indexed by line number.
         */
        questionLines: [],

        /**
         * Initialise one question.
         *
         * @param {String} containerId the id of the div.que that contains this question.
         * @param {boolean} readOnly whether the question is read-only.
         * @param {Object[]} visibleDropZones data on any drop zones to draw as part of the feedback.
         * @param {Object[]} questionLines
         */
        init: function(containerId, readOnly, visibleDropZones, questionLines) {
            questionManager.questions[containerId] =
                new DrawlinesQuestion(containerId, readOnly, visibleDropZones, questionLines);

            questionManager.questions[containerId].updateCoordinates();
            if (!questionManager.eventHandlersInitialised) {
                // Make sure all the images are loaded before setting up resizing event handlers.
                // This was bit tricky as if the images are not loaded then the image height and width would be
                // set to 0, thus causing improper loading of the lines.
                const dropareaimages = document.querySelectorAll('.drawlines .droparea img');
                questionManager.checkAllImagesLoaded(dropareaimages)
                    .then((dropareaimages) => {
                        questionManager.setupEventHandlers();
                        questionManager.eventHandlersInitialised = true;
                        return dropareaimages;
                })
                .catch(error => window.console.error(error));
            }

            if (!questionManager.lineEventHandlersInitialised.hasOwnProperty(containerId)) {
                questionManager.lineEventHandlersInitialised[containerId] = true;

                var questionContainer = document.getElementById(containerId);
                if (questionContainer.classList.contains('drawlines') &&
                    !questionContainer.classList.contains('qtype_drawlines-readonly')) {

                    // Add event listeners to the 'previewArea'.
                    // For dropzone SVG.
                    var dropArea = questionContainer.querySelector('.droparea');
                    // Add event listener for mousedown and touchstart events.
                    dropArea.addEventListener('mousedown', questionManager.handleDropZoneEventMove);
                    dropArea.addEventListener('touchstart', questionManager.handleDropZoneEventMove);
                    // Add event listener for keydown and keypress events.
                    dropArea.addEventListener('keydown', questionManager.handleKeyPress);
                    dropArea.addEventListener('keypress', questionManager.handleKeyPress);

                    dropArea.addEventListener('focusin', function(e) {
                        questionManager.handleKeyboardFocus(e, true);
                    });
                    dropArea.addEventListener('focusout', function(e) {
                        questionManager.handleKeyboardFocus(e, false);
                    });

                    // For draghomes SVG.
                    var drags = questionContainer.querySelector('.draghomes');
                    // Add event listener for mousedown and touchstart events.
                    drags.addEventListener('mousedown', questionManager.handleDragHomeEventMove);
                    drags.addEventListener('touchstart', questionManager.handleDragHomeEventMove);
                    // Add event listener for keydown and keypress events.
                    drags.addEventListener('keydown', questionManager.handleKeyPress);
                    drags.addEventListener('keypress', questionManager.handleKeyPress);

                    drags.addEventListener('focusin', function(e) {
                        questionManager.handleKeyboardFocus(e, true);
                    });
                    drags.addEventListener('focusout', function(e) {
                        questionManager.handleKeyboardFocus(e, false);
                    });
                }
            }
        },

        /**
         * Verify that all the images are loaded on this page.
         * @param {NodeList} images
         **/
        checkAllImagesLoaded: function(images) {
            const promises = Array.from(images).map(img =>
                new Promise((resolve, reject) => {
                    if (img.complete && img.naturalHeight !== 0) {
                        resolve(img); // Image already loaded
                    } else {
                        img.addEventListener('load', () => resolve(img), {once: true});
                        img.addEventListener('error', () => reject(new Error(`Failed to load image: ${img.src}`)), {once: true});
                    }
                })
            );
            return Promise.all(promises);
        },

        /**
         * Set up the event handlers that make this question type work. (Done once per page.)
         */
        setupEventHandlers: function() {
            window.addEventListener('resize', function() {
                questionManager.handleWindowResize(false);
            });
            window.addEventListener('beforeprint', function() {
                questionManager.isPrinting = true;
                questionManager.handleWindowResize(questionManager.isPrinting);
            });
            window.addEventListener('afterprint', function() {
                questionManager.isPrinting = false;
                questionManager.handleWindowResize(questionManager.isPrinting);
            });
            setTimeout(function() {
                questionManager.fixLayoutIfThingsMoved();
            }, 100);
        },

        /**
         * Sometimes, despite our best efforts, things change in a way that cannot
         * be specifically caught (e.g. dock expanding or collapsing in Boost).
         * Therefore, we need to periodically check everything is in the right position.
         */
        fixLayoutIfThingsMoved: function() {
            if (!questionManager.isKeyboardNavigation) {
                this.handleWindowResize(questionManager.isPrinting);
            }
            // We use setTimeout after finishing work, rather than setInterval,
            // in case positioning things is slow. We want 100 ms gap
            // between executions, not what setInterval does.
            setTimeout(function() {
                questionManager.fixLayoutIfThingsMoved(questionManager.isPrinting);
            }, 100);
        },

        /**
         * Handle mouse and touch events for dropzone svg.
         *
         * @param {Event} event
         */
        handleDropZoneEventMove: function(event) {
            var dropzoneElement, dropzoneNo;
            var question = questionManager.getQuestionForEvent(event);
            if (event.target.closest('.dropzone .startcircle.shape')) {
                // Dragging the move handle circle attached to the start of the line.
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                question.handleCircleMove(event, 'startcircle', dropzoneNo);
            } else if (event.target.closest('.dropzone .endcircle.shape')) {
                // Dragging the move handle circle attached to the end of the line.
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                question.handleCircleMove(event, 'endcircle', dropzoneNo);
            } else if (event.target.closest('polyline.shape')) {
                // Dragging the entire line.
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                question.handleLineMove(event, dropzoneNo);
            }
        },

        /**
         * Handle mouse and touch events for dragshome svg.
         *
         * @param {Event} event
         */
        handleDragHomeEventMove: function(event) {
            let dropzoneElement, dropzoneNo,
                question = questionManager.getQuestionForEvent(event);

            if (event.target.closest('g')) {
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                question.handleLineMove(event, dropzoneNo);
                question.saveCoordsForChoice(dropzoneNo);
            }
        },

        /**
         * Handle key down / press events on markers.
         *
         * @param {Event} e
         */
        handleKeyPress: function(e) {
            var question = questionManager.getQuestionForEvent(e);
            var dropzoneElement, dropzoneNo, drag, activeElement;
            if (e.target.closest('.dropzone circle.startcircle')) {
                dropzoneElement = e.target.closest('.dropzone');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                drag = e.target.closest('.dropzone circle.startcircle');
                activeElement = 'startcircle';
            } else if (e.target.closest('.dropzone circle.endcircle')) {
                drag = e.target.closest('.dropzone circle.endcircle');
                dropzoneElement = e.target.closest('.dropzone');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                activeElement = 'endcircle';
            } else if (e.target.closest('g.dropzone')) {
                drag = e.target.closest('g.dropzone');
                dropzoneElement = e.target.closest('.dropzone');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                activeElement = 'line';
            }
            if (question && dropzoneElement) {
                question.handleKeyPress(e, drag, dropzoneNo, activeElement);
            }
        },

        /**
         * Handle when the window is resized.
         * @param {boolean} isPrinting
         */
        handleWindowResize: function(isPrinting) {
            for (var containerId in questionManager.questions) {
                if (questionManager.questions.hasOwnProperty(containerId)) {
                    questionManager.questions[containerId].isPrinting = isPrinting;
                    questionManager.questions[containerId].handleResize();
                }
            }
        },

        /**
         * Handle focus lost events on markers.
         * @param {Event} e
         * @param {boolean} isNavigating
         */
        handleKeyboardFocus: function(e, isNavigating) {
            questionManager.isKeyboardNavigation = isNavigating;
        },

        /**
         * Given an event, work out which question it effects.
         *
         * @param {Event} e the event.
         * @returns {DrawlinesQuestion|undefined} The question, or undefined.
         */
        getQuestionForEvent: function(e) {
            var containerId = $(e.currentTarget).closest('.que.drawlines').attr('id');
            return questionManager.questions[containerId];
        },
    };

    /**
     * @alias module:qtype_drawlines/question
     */
    return {
        /**
         * Initialise one drag-drop markers question.
         *
         * @param {String} containerId id of the outer div for this question.
         * @param {boolean} readOnly whether the question is being displayed read-only.
         * @param {String[]} visibleDropZones the geometry of any drop-zones to show.
         * @param {Object[]} questionLines
         */
        init: questionManager.init,
    };
});
