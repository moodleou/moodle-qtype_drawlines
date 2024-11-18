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
 * This class provides the enhancements to the drawlines editing form.
 *
 * @module     qtype_drawlines/form
 * @copyright  2024 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/dragdrop', 'qtype_drawlines/line'], function($, dragDrop, Line,) {

    /**
     * Create the manager object that deals with keeping everything synchronised for one line.
     *
     * @param {int} lineNo the index of this line in the form. 0, 1, ....
     * @constructor
     */
    function LineManager(lineNo) {
        this.lineNo = lineNo;
        this.svgEl = null;
        this.line = Line.make(this.getCoordinatesFromForm(this.lineNo), this.getLabel(), this.getLineType());
        this.updateCoordinatesFromForm();
    }

    /**
     * Update the coordinates from a particular string.
     *
     * @param {SVGElement} [svg] the SVG element that is the preview.
     */
    LineManager.prototype.updateCoordinatesFromForm = function(svg) {
        var coordinates = this.getCoordinatesFromForm(this.lineNo);

        // Check if the coordinates are in the required format of 'x,y;r'.
        if (!this.validateFormCoordinates(this.lineNo)) {
            return;
        }
        // We don't need to scale the shape for editing form.
        if (!this.line.parse(coordinates[0], coordinates[1], 1)) {
            // Invalid coordinates. Don't update the preview.
            return;
        }

        if (this.line.getCoordinates() !== coordinates) {
            // Line coordinates have changed.
            var currentyActive = this.isActive();
            this.removeFromSvg();
            if (svg) {
                this.addToSvg(svg);
                if (currentyActive) {
                    this.setActive();
                }
            }
        } else {
            // Simple update.
            this.updateSvgEl();
        }
        // Update the rounded coordinates if needed.
        this.setCoordinatesInForm();
    };

    /**
     * Validates if the given coordinates are in the correct format 'x,y;r'.
     *
     * @param {int} lineNo The lineNo of the form.
     * @returns {boolean} True if the coordinates are valid, otherwise false.
     */
    LineManager.prototype.validateFormCoordinates = function(lineNo) {
        var coords = this.getCoordinatesFromForm(lineNo);
        var regexp = /^\d+,\d+;\d+$/;
        return regexp.test(coords[0]) && regexp.test(coords[1]);
    };

    /**
     * Set the coordinates in the form to match the current shape.
     */
    LineManager.prototype.setCoordinatesInForm = function() {
        var linecoords = this.line.getCoordinates();
        drawlinesForm.setFormValue('zonestart', [this.lineNo], linecoords[0]);
        drawlinesForm.setFormValue('zoneend', [this.lineNo], linecoords[1]);
    };

    /**
     * Returns the coordinates for the line from the text input in the form.
     *
     * @param {int} lineNo
     * @returns {Array} the coordinates.
     */
    LineManager.prototype.getCoordinatesFromForm = function(lineNo) {
        var zonestart = drawlinesForm.getFormValue('zonestart', [lineNo]);
        var zoneend = drawlinesForm.getFormValue('zoneend', [lineNo]);
        return [zonestart, zoneend];
    };

    /**
     * Update the labels.
     */
    LineManager.prototype.updateLabel = function() {
        var label = this.getLabel();
        if (this.line.labelstart !== label[0] || this.line.labelend !== label[1]) {
            this.line.labelstart = label[0];
            this.line.labelend = label[1];
            this.updateSvgEl();
        }
    };

    /**
     * Returns the selected type of line in the form.
     *
     * @returns {String} 'linesegment','linesinglearrow', 'linedoublearrows', 'lineinfinite'.
     */
    LineManager.prototype.getLineType = function() {
        return drawlinesForm.getFormValue('type', [this.lineNo]);
    };

    /**
     * Returns the line labels in the form.
     *
     * @returns {Array} line labels text.
     */
    LineManager.prototype.getLabel = function() {
        return [
            drawlinesForm.getFormValue('labelstart', [this.lineNo]),
            drawlinesForm.getFormValue('labelend', [this.lineNo])
        ];
    };

    /**
     * Update the shape of this drop zone (but not type) in an SVG image.
     */
    LineManager.prototype.updateSvgEl = function() {
        if (this.svgEl === null) {
            return;
        }
        if (!this.validateFormCoordinates(this.lineNo)) {
            return;
        }
        this.line.updateSvg(this.svgEl);

        // Adjust handles.
        var handles = this.line.getHandlePositions();
        if (handles === null) {
            return;
        }

        // Move handle.
        // The shape + its labels are the first few children of svgEl.
        // Then comes the move handle followed by the edit handles.
        var i = 0;
        for (i = 0; i < handles.moveHandles.length; ++i) {
            this.svgEl.childNodes[5 + i].setAttribute('cx', handles.moveHandles[i].x);
            this.svgEl.childNodes[5 + i].setAttribute('cy', handles.moveHandles[i].y);
        }

        // Edit handles.
        for (i = 0; i < handles.editHandles.length; ++i) {
            this.svgEl.childNodes[7 + i].setAttribute('x', handles.editHandles[i].x - 6);
            this.svgEl.childNodes[7 + i].setAttribute('y', handles.editHandles[i].y - 6);
        }
    };

    /**
     * Handle if the line type has changed.
     *
     * @param {SVGElement} [svg] an SVG element to add this new shape to.
     */
    LineManager.prototype.changeShape = function(svg) {
        var newLineType = this.getLineType(),
            currentyActive = this.isActive();
        if (newLineType === this.line.getType()) {
            return;
        }

        // It has really changed.
        this.removeFromSvg();
        if (newLineType !== 'choose') {
            this.line = Line.getSimilar(newLineType, this.line);
            if (svg) {
                this.addToSvg(svg);
                if (currentyActive) {
                    this.setActive();
                }
            }
        }
    };

    /**
     * Find out if this line element is currently being edited.
     *
     * @return {boolean} true if it is.
     */
    LineManager.prototype.isActive = function() {
        return this.svgEl !== null && this.svgEl.getAttribute('class').match(/\bactive\b/);
    };

    /**
     * Set this line element as being edited.
     */
    LineManager.prototype.setActive = function() {
        this.svgEl.setAttribute('class', this.svgEl.getAttribute('class') + ' active');
    };

    /**
     * Add this line to an SVG graphic.
     *
     * @param {SVGElement} svg the SVG image to which to add this drop zone.
     */
    LineManager.prototype.addToSvg = function(svg) {
        if (this.svgEl !== null) {
            throw new Error('this.svgEl already set');
        }
        if (!this.validateFormCoordinates(this.lineNo)) {
            return;
        }
        this.svgEl = this.line.makeSvg(svg);
        if (!this.svgEl) {
            return;
        }
        this.svgEl.setAttribute('class', 'dropzone');
        this.svgEl.setAttribute('data-dropzone-no', this.lineNo);

        // Add handles.
        var handles = this.line.getHandlePositions();
        if (handles === null) {
            return;
        }

        // Add handles to the line points.
        this.makeMoveHandle(0, handles.moveHandles[0], "handlestart move");
        this.makeMoveHandle(1, handles.moveHandles[1], "handleend move");

        // Add edithandles to the circles to set the start and end radius.
        this.makeEditHandle(0, handles.editHandles[0], "handlestart edit");
        this.makeEditHandle(1, handles.editHandles[1], "handleend edit");
    };

    /**
     * Add a new move handle.
     *
     * @param {int} index the handle index.
     * @param {Point} point the point at which to add the handle.
     * @param {String} handleclass the class attribute to add to the handle.
     */
    LineManager.prototype.makeMoveHandle = function(index, point, handleclass) {
        var moveHandle = Line.createSvgElement(this.svgEl, 'circle');
        moveHandle.setAttribute('cx', point.x);
        moveHandle.setAttribute('cy', point.y);
        moveHandle.setAttribute('r', 7);
        moveHandle.setAttribute('class', handleclass);
        moveHandle.setAttribute('data-move-handle-no', index);
        moveHandle.setAttribute('tabindex', 0);
    };

    /**
     * Add a new edit handle.
     *
     * @param {int} index the handle index.
     * @param {Point} point the point at which to add the handle.
     * @param {String} handleclass the class attribute to add to the handle.
     */
    LineManager.prototype.makeEditHandle = function(index, point, handleclass) {
        var editHandle = Line.createSvgElement(this.svgEl, 'rect');
        editHandle.setAttribute('x', point.x - 6);
        editHandle.setAttribute('y', point.y - 6);
        editHandle.setAttribute('width', 11);
        editHandle.setAttribute('height', 11);
        editHandle.setAttribute('class', handleclass);
        editHandle.setAttribute('data-edit-handle-no', index);
        editHandle.setAttribute('tabindex', 0);
    };

    /**
     * Make an invisible drag proxy.
     *
     * @param {int} x x position .
     * @param {int} y y position.
     * @returns {HTMLElement} the drag proxy.
     */
    LineManager.prototype.makeDragProxy = function(x, y) {
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
     * Start responding to dragging the line elements.
     *
     * @param {Event} e Event object
     * @param {String} handleIndex
     * @param {String} handleType
     */
    LineManager.prototype.handleMouseEvents = function(e, handleIndex, handleType) {
        var info = dragDrop.prepare(e);
        if (!info.start) {
            return;
        }

        var changingDropZone = this,
            lastX = parseInt(info.x),
            lastY = parseInt(info.y),
            dragProxy = this.makeDragProxy(info.x, info.y),
            bgImg = document.querySelector('fieldset#id_previewareaheader .dropbackground'),
            maxX = parseInt(bgImg.width),
            maxY = parseInt(bgImg.height);

        dragDrop.start(e, $(dragProxy), function(pageX, pageY) {
            switch (handleType) {
                case 'edit':
                    changingDropZone.line.edit(handleIndex, parseInt(pageX) - lastX,
                        parseInt(pageY) - lastY, maxX, maxY);
                    changingDropZone.line.normalizeShape();
                    break;
                case 'move':
                    changingDropZone.line.move(handleIndex, parseInt(pageX) - lastX,
                        parseInt(pageY) - lastY, maxX, maxY);
                    break;
                case 'line':
                    changingDropZone.line.moveDrags(
                        parseInt(pageX) - lastX, parseInt(pageY) - lastY, maxX, maxY, '');
                    break;
            }
            lastX = pageX;
            lastY = pageY;
            changingDropZone.updateSvgEl();
            changingDropZone.setCoordinatesInForm();
        }, function() {
            document.body.removeChild(dragProxy);
        });
    };

    /**
     * Handle key down / press events on markers.
     *
     * @param {Event} event
     * @param {SVGElement} drag SVG element being dragged.
     * @param {String} handleIndex which line handle was moved.
     * @param {String} handleType the type of handle - edit, move or line.
     */
    LineManager.prototype.handleKeyPress = function(event, drag, handleIndex, handleType) {
        var x = 0,
            y = 0;
        switch (event.code) {
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
        event.preventDefault();

        // Get the dimensions of the selected element's svg.
        var bgImg = document.querySelector('fieldset#id_previewareaheader .dropbackground'),
            maxX = bgImg.width,
            maxY = bgImg.height;

        if (handleType === 'move') {
            this.line.move(handleIndex, parseInt(x), parseInt(y), parseInt(maxX), parseInt(maxY));
        } else if (handleType === 'edit') {
            this.line.edit(handleIndex, parseInt(x), parseInt(y), parseInt(maxX), parseInt(maxY));
            this.line.normalizeShape();
        } else if (handleType === 'line') {
            this.line.moveDrags(parseInt(x), parseInt(y), parseInt(maxX), parseInt(maxY), '');
        }
        this.updateSvgEl();
        this.setCoordinatesInForm();
        drag.focus();
    };

    /**
     * Remove this line from an SVG image.
     */
    LineManager.prototype.removeFromSvg = function() {
        if (this.svgEl !== null) {
            this.svgEl.parentNode.removeChild(this.svgEl);
            this.svgEl = null;
        }
    };

    /**
     * Singleton object for managing all the parts of the form.
     */
    const drawlinesForm = {

        /**
         * @var {object} for interacting with the file pickers.
         */
        fp: null, // Object containing functions associated with the file picker.

        /**
         * @var {int} the number of lines on the form.
         */
        noOfLines: null,

        /**
         * @var {LineManager[]} the lines in the preview, indexed by line number.
         */
        dropZones: [],

        /**
         * Init method.
         */
        init: function() {
            drawlinesForm.noOfLines = drawlinesForm.getFormValue('numberoflines', []);
            drawlinesForm.createShapes();
            drawlinesForm.fp = drawlinesForm.filePickers();
            drawlinesForm.setupEventHandlers();
            drawlinesForm.waitForFilePickerToInitialise();
        },

        /**
         * Utility to get the file name and url from the filepicker.
         *
         * @returns {Object} object containing functions {file, name}
         */
        filePickers: function() {
            var draftItemIdsToName;
            var nameToParentNode;
            if (draftItemIdsToName === undefined) {
                draftItemIdsToName = {};
                nameToParentNode = {};
                var fp = document.querySelectorAll('form.mform[data-qtype="drawlines"] input.filepickerhidden');
                fp.forEach(function(filepicker) {
                    draftItemIdsToName[filepicker.value] = filepicker.name;
                    nameToParentNode[filepicker.name] = filepicker.parentNode;
                });
            }

            return {
                file: function(name) {
                    var parentNode = nameToParentNode[name];
                    if (parentNode) {
                        var fileAnchor = parentNode.querySelector('div.filepicker-filelist a');
                        if (fileAnchor) {
                            return {href: fileAnchor.href, name: fileAnchor.innerHTML};
                        }
                    }
                    return {href: null, name: null};
                },

                name: function(draftitemid) {
                    return draftItemIdsToName[draftitemid];
                }
            };
        },

        /**
         * Loads the preview background image.
         */
        loadPreviewImage: function() {
            if (!document.getElementById('dlines-droparea')) {
                drawlinesForm.setupPreviewArea();
            }
            var img = document.querySelector('fieldset#id_previewareaheader .dropbackground');
            if (img) {
                img.addEventListener('load', function() {
                    drawlinesForm.afterPreviewImageLoaded();
                }, {once: true});
                img.src = drawlinesForm.fp.file('bgimage').href;
            }
        },

        /**
         * Add html for the preview area.
         */
        setupPreviewArea: function() {
            var previewareaheader = document.querySelector('fieldset#id_previewareaheader');
            if (drawlinesForm.fp.file('bgimage').href !== null) {
                previewareaheader.insertAdjacentHTML('beforeend',
                    '<div class="ddarea que drawlines">' +
                    '  <div id="dlines-droparea" class="droparea">' +
                    '    <img class="dropbackground" />' +
                    '    <div id="dlines-dropzone" class="dropzones"></div>' +
                    '  </div>' +
                    '  <div class="dragitems"></div>' +
                    '</div>');
            }
        },

        /**
         * Events linked to form actions.
         */
        setupEventHandlers: function() {
            // Changes to Drop zones section: shape, coordinates and marker.
            var lineSelector = 'fieldset#id_linexheader_' + '0';

            for (var lineNo = 0; lineNo < drawlinesForm.noOfLines; lineNo++) {
                lineSelector = 'fieldset#id_linexheader_' + lineNo;
                document.querySelector(lineSelector).addEventListener('input', function(e) {
                    if (e.target.matches('input, select')) {
                        var ids = e.target.name.match(/^([a-z]*)\[(\d+)]$/);
                        var id = e.target.name;
                        if (!id) {
                            return;
                        }
                        var dropzoneNo = ids[2],
                            inputType = ids[1],
                            dropZone = drawlinesForm.dropZones[dropzoneNo];

                        switch (inputType) {
                            case 'zonestart':
                            case 'zoneend':
                                dropZone.updateCoordinatesFromForm(drawlinesForm.getSvg());
                                break;

                            case 'type':
                                dropZone.updateCoordinatesFromForm(drawlinesForm.getSvg());
                                dropZone.changeShape(drawlinesForm.getSvg());
                                break;

                            case 'labelstart':
                            case 'labelend':
                                dropZone.updateLabel();
                                break;
                        }
                    }
                });
            }

            // Click to toggle graphical editing.
            var previewArea = document.querySelector('fieldset#id_previewareaheader');
            previewArea.addEventListener('click', function(event) {
                if (event.target.closest('g.dropzone')) {
                    var dropzoneElement = event.target.closest('g.dropzone');
                    drawlinesForm.setElementActive(dropzoneElement);
                } else {
                    drawlinesForm.setElementActive(null);
                }
            });
            previewArea.addEventListener('keydown', function(event) {
                if (event.target.closest('g.dropzone')) {
                    var dropzoneElement = event.target.closest('g.dropzone');
                    drawlinesForm.setElementActive(dropzoneElement);
                }
            });

            // Add event listeners to the 'previewArea'.
            previewArea.addEventListener('mousedown', drawlinesForm.handleEventLine);
            previewArea.addEventListener('touchstart', drawlinesForm.handleEventLine);
            previewArea.addEventListener('mousedown', drawlinesForm.handleEventMove);
            previewArea.addEventListener('touchstart', drawlinesForm.handleEventMove);
            previewArea.addEventListener('mousedown', drawlinesForm.handleEventEdit);
            previewArea.addEventListener('touchstart', drawlinesForm.handleEventEdit);
            // Add keyboard events.
            previewArea.addEventListener('keydown', drawlinesForm.handleKeyPress);
            previewArea.addEventListener('keypress', drawlinesForm.handleKeyPress);
        },

        /**
         * Set the element as active.
         *
         * @param {SVGElement|null} dropzoneElement SVG element to set active or null to remove.
         */
        setElementActive: function(dropzoneElement) {
            let svgElement, activeDropzones;
            if (dropzoneElement !== null) {
                let dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                let currentlyActive = drawlinesForm.dropZones[dropzoneNo].isActive();
                if (!currentlyActive) {
                    // Find all active dropzones and remove the 'active' class
                    svgElement = drawlinesForm.getSvg();
                    activeDropzones = svgElement.querySelectorAll('.dropzone.active');
                    activeDropzones.forEach(function(activeDropzone) {
                        activeDropzone.classList.remove('active');
                    });
                    drawlinesForm.dropZones[dropzoneNo].setActive();
                }
            } else {
                // When mouse is clicked away from the line element, the active class should be removed.
                svgElement = drawlinesForm.getSvg();
                activeDropzones = svgElement.querySelectorAll('.dropzone.active');
                activeDropzones.forEach(function(activeDropzone) {
                    activeDropzone.classList.remove('active');
                });
            }
        },

        /**
         * Handle events linked to moving the line.
         *
         * @param {Event} event
         */
        handleEventMove: function(event) {
            var dropzoneElement, dropzoneNo, handleIndex;
            if (event.target.closest('.dropzone .handlestart.move')) {
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = 'startcircle';
                drawlinesForm.dropZones[dropzoneNo].handleMouseEvents(event, handleIndex, 'move');
            } else if (event.target.closest('.dropzone .handleend.move')) {
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = 'endcircle';
                drawlinesForm.dropZones[dropzoneNo].handleMouseEvents(event, handleIndex, 'move');
            }
        },

        /**
         * Handle events linked to moving the rectangle to change the radius which is used for grading.
         *
         * @param {Event} event
         */
        handleEventEdit: function(event) {
            var dropzoneElement, dropzoneNo, handleIndex;
            if (event.target.closest('.dropzone .handlestart.edit')) {
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = event.target.getAttribute('data-edit-handle-no');
                drawlinesForm.dropZones[dropzoneNo].handleMouseEvents(event, handleIndex, 'edit');
            } else if (event.target.closest('.dropzone .handleend.edit')) {
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = event.target.getAttribute('data-edit-handle-no');
                drawlinesForm.dropZones[dropzoneNo].handleMouseEvents(event, handleIndex, 'edit');
            }
        },

        /**
         * Handle events linked to moving the line.
         *
         * @param {Event} event
         */
        handleEventLine: function(event) {
            var dropzoneElement, dropzoneNo;
            if (event.target.closest('g.dropzone.active')) {
                dropzoneElement = event.target.closest('g.active');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                drawlinesForm.dropZones[dropzoneNo].handleMouseEvents(event, '', 'line');
            }
        },

        /**
         * Handle key down / press events on lines.
         *
         * @param {Event} e
         */
        handleKeyPress: function(e) {
            var dropzoneElement, dropzoneNo, handleIndex, drag;

            if (event.target.closest('.dropzone.active .handlestart.move')) {
                // Handle moving startcircle of a line.
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = 'startcircle';
                drag = e.target.closest('.dropzone.active .handlestart.move');
                drawlinesForm.dropZones[dropzoneNo].handleKeyPress(event, drag, handleIndex, 'move');
            } else if (event.target.closest('.dropzone.active .handleend.move')) {
                // Handle moving endcircle of a line.
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = 'endcircle';
                drag = e.target.closest('.dropzone.active .handleend.move');
                drawlinesForm.dropZones[dropzoneNo].handleKeyPress(event, drag, handleIndex, 'move');
            } else if (event.target.closest('.dropzone.active .handlestart.edit')) {
                // Handle editing radius for start point of a line.
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = event.target.getAttribute('data-edit-handle-no');
                drag = e.target.closest('.dropzone.active .handlestart.edit');
                drawlinesForm.dropZones[dropzoneNo].handleKeyPress(event, drag, handleIndex, 'edit');
            } else if (event.target.closest('.dropzone.active .handleend.edit')) {
                // Handle editing radius for end point of a line.
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = event.target.getAttribute('data-edit-handle-no');
                drag = e.target.closest('.dropzone.active .handleend.edit');
                drawlinesForm.dropZones[dropzoneNo].handleKeyPress(event, drag, handleIndex, 'edit');
            } else if (e.target.closest('g.dropzone')) {
                // Handle moving entire line.
                dropzoneElement = event.target.closest('.dropzone');
                // DrawlinesForm.setElementActive(dropzoneElement);
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                drag = e.target.closest('g.dropzone.active');
                drawlinesForm.dropZones[dropzoneNo].handleKeyPress(event, drag, '', 'line');
            }
        },

        /**
         * Waits for the file-pickers to be sufficiently ready before initialising the preview.
         */
        waitForFilePickerToInitialise: function() {
            // Add event listener for change events on the file picker elements
            document.querySelectorAll('form.mform[data-qtype="drawlines"]').forEach(function(form) {
                form.addEventListener('change', drawlinesForm.loadPreviewImage);
            });

            // Check if the element with id 'id_droparea' exists
            if (document.getElementById('dlines-droparea')) {
                drawlinesForm.loadPreviewImage();
            } else {
                // Setup preview area when the background image is uploaded the first time
                drawlinesForm.setupPreviewArea();
                drawlinesForm.loadPreviewImage();
            }
        },

        /**
         * Functions to run after background image loaded.
         */
        afterPreviewImageLoaded: function() {
            var bgImg = document.querySelector('fieldset#id_previewareaheader .dropbackground');
            // Place the dropzone area over the background image (adding one to account for the border).
            document.getElementById('dlines-dropzone').style.position = 'relative';
            document.getElementById('dlines-dropzone').style.top = (bgImg.height + 1) * -1 + "px";
            document.getElementById('dlines-droparea').style.height = bgImg.height + 20 + "px";
            drawlinesForm.updateSvgDisplay();
        },

        /**
         * Draws or re-draws all dropzones in the preview area based on form data.
         * Call this function when there is a change in the form data.
         */
        updateSvgDisplay: function() {
            var bgImg = document.querySelector('fieldset#id_previewareaheader .dropbackground');

            if (drawlinesForm.getSvg()) {
                // Already exists, just need to be updated.
                for (var lineNo = 0; lineNo < drawlinesForm.noOfLines; lineNo++) {
                    drawlinesForm.dropZones[lineNo].updateSvgEl();
                }

            } else {
                // Create.
                document.getElementById('dlines-dropzone').innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" class="dropzones" ' +
                    'width="' + bgImg.width + '" ' +
                    'height="' + bgImg.height + '">' +
                    '</svg>';
                for (var lines = 0; lines < drawlinesForm.noOfLines; lines++) {
                    drawlinesForm.dropZones[lines].addToSvg(drawlinesForm.getSvg());
                }
            }
        },

        /**
         * Get the SVG element, if there is one, otherwise return null.
         *
         * @returns {SVGElement|null} the SVG element or null.
         */
        getSvg: function() {
            var svg = document.querySelector('fieldset#id_previewareaheader svg');
            if (svg === null) {
                return null;
            } else {
                return svg;
            }
        },

        toNameWithIndex: function(name, indexes) {
            var indexString = name;
            for (var i = 0; i < indexes.length; i++) {
                indexString = indexString + '[' + indexes[i] + ']';
            }
            return indexString;
        },

        getEl: function(name, indexes) {
            var form = document.querySelector('form.mform[data-qtype="drawlines"]');
            return form.elements[this.toNameWithIndex(name, indexes)];
        },

        /**
         * Helper to get the value of a form elements with name like "zonestart[0]".
         *
         * @param {String} name the base name, e.g. 'zonestart'.
         * @param {String[]} indexes the indexes, e.g. ['0'].
         * @return {String} the value of that field.
         */
        getFormValue: function(name, indexes) {
            var el = this.getEl(name, indexes);
            return el.value;
        },

        /**
         * Helper to get the value of a form elements with name like "zonestart[0]".
         *
         * @param {String} name the base name, e.g. 'zonestart'.
         * @param {String[]} indexes the indexes, e.g. ['0'].
         * @param {String} value the value to set.
         */
        setFormValue: function(name, indexes, value) {
            var el = this.getEl(name, indexes);
            if (el.type === 'checkbox') {
                el.checked = value;
            } else {
                el.value = value;
            }
        },

        /**
         * Create the shape representation of each dropZone.
         */
        createShapes: function() {
            for (var lineNo = 0; lineNo < drawlinesForm.noOfLines; lineNo++) {
                drawlinesForm.dropZones[lineNo] = new LineManager(lineNo);
            }
        },

    };

    /**
     * @alias module:qtype_ddmarker/form
     */
    return {
        /**
         * Initialise the form javascript features.
         * @param {Object} maxBgimageSize object with two properties: width and height.
         */
        init: drawlinesForm.init
    };
});
