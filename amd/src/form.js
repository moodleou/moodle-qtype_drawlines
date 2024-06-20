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
 * JavaScript to allow dragging options to slots (using mouse down or touch) or tab through slots using keyboard.
 *
 * @module     qtype_drawlines/form
 * @copyright  2024 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/dragdrop', 'qtype_drawlines/Line'], function($, dragDrop, Line,) {
    /**
     * Create the manager object that deals with keeping everything synchronised for one line.
     *
     * @param {int} lineNo the index of this line in the form. 0, 1, ....
     * @constructor
     */
    function LineManager(lineNo) {
        this.lineNo = lineNo;
        this.svgEl = null;
        this.line = Line.make(this.getCoordinates(), this.getLabel(), this.getLineType());
        this.updateCoordinatesFromForm();
    }
    /**
     * Update the coordinates from a particular string.
     */
    LineManager.prototype.updateCoordinatesFromForm = function() {
        var coordinates = this.getCoordinates();
        if (this.line.getCoordinates() === coordinates) {
            return;
        }
        // We don't need to scale the shape for editing form.
        if (!this.line.parse(coordinates[0], coordinates[1], 1)) {
            // Invalid coordinates. Don't update the preview.
            return;
        }

        this.updateSvgEl();
        // Update the rounded coordinates if needed.
        this.setCoordinatesInForm();
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
     * Set the coordinates in the form to match the current shape.
     */
    LineManager.prototype.setCoordinatesInForm = function() {
        var linecoords = this.line.getCoordinates();
        drawlinesForm.setFormValue('zonestart', [this.lineNo], linecoords[0]);
        drawlinesForm.setFormValue('zoneend', [this.lineNo], linecoords[1]);
    };

    /**
     * Returns the coordinates for the line from the text input in the form.
     * @returns {Array} the coordinates.
     */
    LineManager.prototype.getCoordinates = function() {
        var zonestart = drawlinesForm.getFormValue('zonestart', [this.lineNo]);
        var zoneend = drawlinesForm.getFormValue('zoneend', [this.lineNo]);
        return [zonestart, zoneend];
    };

    /**
     * Returns the selected type of line in the form.
     * @returns {String} 'linesegment','linesinglearrow', 'linedoublearrows', 'lineinfinite'.
     */
    LineManager.prototype.getLineType = function() {
        return drawlinesForm.getFormValue('type', [this.lineNo]);
    };

    /**
     * Returns the line labels in the form.
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
        this.line.updateSvg(this.svgEl);

        // Adjust handles.
        var handles = this.line.getHandlePositions();
        if (handles === null) {
            return;
        }

        // Move handle.
        // The shape + its labels are the first few children of svgEl.
        // Then come the move handle followed by the edit handles.
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
        this.line = Line.getSimilar(newLineType, this.line);
        if (svg) {
            this.addToSvg(svg);
            if (currentyActive) {
                this.setActive();
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
        // Move this one to last, so that it is always on top.
        // (Otherwise the handles may not be able to receive events.)
        var parent = this.svgEl.parentNode;
        parent.removeChild(this.svgEl);
        parent.appendChild(this.svgEl);
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
    };

    /**
     * Start responding to dragging the move handle.
     * @param {Event} e Event object
     * @param {String} handleIndex
     */
    LineManager.prototype.handleMove = function(e, handleIndex) {
        var info = dragDrop.prepare(e);
        if (!info.start) {
            return;
        }

        var movingDropZone = this,
            lastX = info.x,
            lastY = info.y,
            dragProxy = this.makeDragProxy(info.x, info.y),
            bgImg = document.querySelector('fieldset#id_previewareaheader .dropbackground'),
            maxX = bgImg.width,
            maxY = bgImg.height;

        dragDrop.start(e, $(dragProxy), function(pageX, pageY) {
            movingDropZone.line.move(handleIndex, pageX - lastX, pageY - lastY, maxX, maxY);
            lastX = pageX;
            lastY = pageY;
            movingDropZone.updateSvgEl();
            movingDropZone.setCoordinatesInForm();
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
     * Start responding to dragging the move handle.
     * @param {Event} e Event object
     * @param {String} handleIndex
     */
    LineManager.prototype.handleEdit = function(e, handleIndex) {
        var info = dragDrop.prepare(e);
        if (!info.start) {
            return;
        }

        var changingDropZone = this,
            lastX = info.x,
            lastY = info.y,
            dragProxy = this.makeDragProxy(info.x, info.y),
            bgImg = document.querySelector('fieldset#id_previewareaheader .dropbackground'),
            maxX = bgImg.width,
            maxY = bgImg.height;

        dragDrop.start(e, $(dragProxy), function(pageX, pageY) {
            changingDropZone.line.edit(handleIndex, pageX - lastX, pageY - lastY, maxX, maxY);
            lastX = pageX;
            lastY = pageY;
            changingDropZone.updateSvgEl();
            changingDropZone.setCoordinatesInForm();
        }, function() {
            document.body.removeChild(dragProxy);
            changingDropZone.line.normalizeShape();
            changingDropZone.updateSvgEl();
            changingDropZone.setCoordinatesInForm();
        });
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

                    var dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                    var currentlyActive = drawlinesForm.dropZones[dropzoneNo].isActive();

                    // Find all active dropzones and remove the 'active' class
                    var svgElement = drawlinesForm.getSvg();
                    var activeDropzones = svgElement.querySelectorAll('.dropzone.active');
                    activeDropzones.forEach(function(activeDropzone) {
                        activeDropzone.classList.remove('active');
                    });

                    // If the dropzone was not active, set it as active
                    if (!currentlyActive) {
                        drawlinesForm.dropZones[dropzoneNo].setActive();
                    }
                }
            });

            // Add event listeners to the 'previewArea'.
            previewArea.addEventListener('mousedown', drawlinesForm.handleEventMove);
            previewArea.addEventListener('touchstart', drawlinesForm.handleEventMove);
            previewArea.addEventListener('mousedown', drawlinesForm.handleEventEdit);
            previewArea.addEventListener('touchstart', drawlinesForm.handleEventEdit);
        },

        handleEventMove: function(event) {
            var dropzoneElement, dropzoneNo, handleIndex;
            if (event.target.closest('.dropzone .handlestart.move')) {
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = event.target.getAttribute('data-move-handle-no');
                drawlinesForm.dropZones[dropzoneNo].handleMove(event, handleIndex);
            } else if (event.target.closest('.dropzone .handleend.move')) {
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = event.target.getAttribute('data-move-handle-no');
                drawlinesForm.dropZones[dropzoneNo].handleMove(event, handleIndex);
            }
        },

        handleEventEdit: function(event) {
            var dropzoneElement, dropzoneNo, handleIndex;
            if (event.target.closest('.dropzone .handlestart.edit')) {
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = event.target.getAttribute('data-edit-handle-no');
                drawlinesForm.dropZones[dropzoneNo].handleEdit(event, handleIndex);
            } else if (event.target.closest('.dropzone .handleend.edit')) {
                dropzoneElement = event.target.closest('g');
                dropzoneNo = dropzoneElement.dataset.dropzoneNo;
                handleIndex = event.target.getAttribute('data-edit-handle-no');
                drawlinesForm.dropZones[dropzoneNo].handleEdit(event, handleIndex);
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