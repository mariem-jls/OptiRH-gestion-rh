(self["webpackChunk"] = self["webpackChunk"] || []).push([["app"],{

/***/ "./assets/app.js":
/*!***********************!*\
  !*** ./assets/app.js ***!
  \***********************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _styles_app_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./styles/app.css */ "./assets/styles/app.css");
/* harmony import */ var _public_bundles_addressing_js_countryCodeChange__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../public/bundles/addressing/js/countryCodeChange */ "./public/bundles/addressing/js/countryCodeChange.js");
/* harmony import */ var _public_bundles_addressing_js_countryCodeChange__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_public_bundles_addressing_js_countryCodeChange__WEBPACK_IMPORTED_MODULE_1__);
/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)



/***/ }),

/***/ "./assets/styles/app.css":
/*!*******************************!*\
  !*** ./assets/styles/app.css ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./public/bundles/addressing/js/countryCodeChange.js":
/*!***********************************************************!*\
  !*** ./public/bundles/addressing/js/countryCodeChange.js ***!
  \***********************************************************/
/***/ ((module, __unused_webpack_exports, __webpack_require__) => {

__webpack_require__(/*! core-js/modules/es.array.find.js */ "./node_modules/core-js/modules/es.array.find.js");
__webpack_require__(/*! core-js/modules/es.object.to-string.js */ "./node_modules/core-js/modules/es.object.to-string.js");
__webpack_require__(/*! core-js/modules/esnext.iterator.constructor.js */ "./node_modules/core-js/modules/esnext.iterator.constructor.js");
__webpack_require__(/*! core-js/modules/esnext.iterator.find.js */ "./node_modules/core-js/modules/esnext.iterator.find.js");
module.exports = {
  // The address form fields are initialized here, these form fields are common for some entity forms and are therefore put in this function
  initialize: function initialize() {
    $(document).ready(function () {
      $('.address-embeddable').once('initiate-country-code-change').each(function () {
        var id = $(this).attr('id');

        // When the country code changes the form will be submitted so we get a validated form for that country
        // Only the address part here is important to change in the current view
        var _onCountryCodeChange = function onCountryCodeChange() {
          var $form = $(this).closest('form');
          var $countryCode = $(this);
          var $address = $countryCode.closest('.address-embeddable');
          var data = {};
          $addressElements = $address.find('.form-control');
          $addressElements.each(function (index, element) {
            data[$(element).attr('name')] = $(element).val();
          });
          $.ajax({
            url: $form.attr('action'),
            type: $form.attr('method'),
            data: data,
            success: function success(html) {
              $address.replaceWith($(html).find('#' + id));
              var $countryCode = $form.find('#' + id + '_countryCode');
              $countryCode.change(_onCountryCodeChange);
              $countryCode.closest('.address-embeddable').trigger('countryCodeChanged');
            }
          });
        };
        $countryCode = $('#' + id + '_countryCode');
        $countryCode.change(_onCountryCodeChange);
      });
    });
  }
};

/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ __webpack_require__.O(0, ["vendors-node_modules_core-js_modules_es_array_find_js-node_modules_core-js_modules_es_object_-442574"], () => (__webpack_exec__("./assets/app.js")));
/******/ var __webpack_exports__ = __webpack_require__.O();
/******/ }
]);
//# sourceMappingURL=data:application/json;charset=utf-8;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiYXBwLmpzIiwibWFwcGluZ3MiOiI7Ozs7Ozs7Ozs7Ozs7QUFBQTtBQUNBO0FBQ0E7QUFDQTtBQUNBO0FBQ0E7O0FBRUE7QUFDMEI7Ozs7Ozs7Ozs7Ozs7QUNSMUI7Ozs7Ozs7Ozs7Ozs7OztBQ0FBQSxNQUFNLENBQUNDLE9BQU8sR0FBRztFQUNiO0VBQ0FDLFVBQVUsRUFBRSxTQUFaQSxVQUFVQSxDQUFBLEVBQWM7SUFDcEJDLENBQUMsQ0FBQ0MsUUFBUSxDQUFDLENBQUNDLEtBQUssQ0FBQyxZQUFZO01BQzFCRixDQUFDLENBQUMscUJBQXFCLENBQUMsQ0FBQ0csSUFBSSxDQUFDLDhCQUE4QixDQUFDLENBQUNDLElBQUksQ0FBQyxZQUFZO1FBQzNFLElBQUlDLEVBQUUsR0FBR0wsQ0FBQyxDQUFDLElBQUksQ0FBQyxDQUFDTSxJQUFJLENBQUMsSUFBSSxDQUFDOztRQUUzQjtRQUNBO1FBQ0EsSUFBSUMsb0JBQW1CLEdBQUcsU0FBdEJBLG1CQUFtQkEsQ0FBQSxFQUFlO1VBQ2xDLElBQUlDLEtBQUssR0FBR1IsQ0FBQyxDQUFDLElBQUksQ0FBQyxDQUFDUyxPQUFPLENBQUMsTUFBTSxDQUFDO1VBQ25DLElBQUlDLFlBQVksR0FBR1YsQ0FBQyxDQUFDLElBQUksQ0FBQztVQUMxQixJQUFJVyxRQUFRLEdBQUdELFlBQVksQ0FBQ0QsT0FBTyxDQUFDLHFCQUFxQixDQUFDO1VBRTFELElBQUlHLElBQUksR0FBRyxDQUFDLENBQUM7VUFDYkMsZ0JBQWdCLEdBQUdGLFFBQVEsQ0FBQ0csSUFBSSxDQUFDLGVBQWUsQ0FBQztVQUNqREQsZ0JBQWdCLENBQUNULElBQUksQ0FBQyxVQUFVVyxLQUFLLEVBQUVDLE9BQU8sRUFBRTtZQUM1Q0osSUFBSSxDQUFDWixDQUFDLENBQUNnQixPQUFPLENBQUMsQ0FBQ1YsSUFBSSxDQUFDLE1BQU0sQ0FBQyxDQUFDLEdBQUdOLENBQUMsQ0FBQ2dCLE9BQU8sQ0FBQyxDQUFDQyxHQUFHLENBQUMsQ0FBQztVQUNwRCxDQUFDLENBQUM7VUFFRmpCLENBQUMsQ0FBQ2tCLElBQUksQ0FBQztZQUNIQyxHQUFHLEVBQUVYLEtBQUssQ0FBQ0YsSUFBSSxDQUFDLFFBQVEsQ0FBQztZQUN6QmMsSUFBSSxFQUFFWixLQUFLLENBQUNGLElBQUksQ0FBQyxRQUFRLENBQUM7WUFDMUJNLElBQUksRUFBRUEsSUFBSTtZQUNWUyxPQUFPLEVBQUUsU0FBVEEsT0FBT0EsQ0FBWUMsSUFBSSxFQUFFO2NBQ3JCWCxRQUFRLENBQUNZLFdBQVcsQ0FDaEJ2QixDQUFDLENBQUNzQixJQUFJLENBQUMsQ0FBQ1IsSUFBSSxDQUFDLEdBQUcsR0FBR1QsRUFBRSxDQUN6QixDQUFDO2NBQ0QsSUFBSUssWUFBWSxHQUFHRixLQUFLLENBQUNNLElBQUksQ0FBQyxHQUFHLEdBQUdULEVBQUUsR0FBRyxjQUFjLENBQUM7Y0FDeERLLFlBQVksQ0FBQ2MsTUFBTSxDQUFDakIsb0JBQW1CLENBQUM7Y0FDeENHLFlBQVksQ0FBQ0QsT0FBTyxDQUFDLHFCQUFxQixDQUFDLENBQUNnQixPQUFPLENBQUMsb0JBQW9CLENBQUM7WUFDN0U7VUFDSixDQUFDLENBQUM7UUFDTixDQUFDO1FBRURmLFlBQVksR0FBR1YsQ0FBQyxDQUFDLEdBQUcsR0FBR0ssRUFBRSxHQUFHLGNBQWMsQ0FBQztRQUMzQ0ssWUFBWSxDQUFDYyxNQUFNLENBQUNqQixvQkFBbUIsQ0FBQztNQUM1QyxDQUFDLENBQUM7SUFDTixDQUFDLENBQUM7RUFDTjtBQUNKLENBQUMiLCJzb3VyY2VzIjpbIndlYnBhY2s6Ly8vLi9hc3NldHMvYXBwLmpzIiwid2VicGFjazovLy8uL2Fzc2V0cy9zdHlsZXMvYXBwLmNzcz8zZmJhIiwid2VicGFjazovLy8uL3B1YmxpYy9idW5kbGVzL2FkZHJlc3NpbmcvanMvY291bnRyeUNvZGVDaGFuZ2UuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLypcbiAqIFdlbGNvbWUgdG8geW91ciBhcHAncyBtYWluIEphdmFTY3JpcHQgZmlsZSFcbiAqXG4gKiBXZSByZWNvbW1lbmQgaW5jbHVkaW5nIHRoZSBidWlsdCB2ZXJzaW9uIG9mIHRoaXMgSmF2YVNjcmlwdCBmaWxlXG4gKiAoYW5kIGl0cyBDU1MgZmlsZSkgaW4geW91ciBiYXNlIGxheW91dCAoYmFzZS5odG1sLnR3aWcpLlxuICovXG5cbi8vIGFueSBDU1MgeW91IGltcG9ydCB3aWxsIG91dHB1dCBpbnRvIGEgc2luZ2xlIGNzcyBmaWxlIChhcHAuY3NzIGluIHRoaXMgY2FzZSlcbmltcG9ydCAnLi9zdHlsZXMvYXBwLmNzcyc7XG5pbXBvcnQgJy4uL3B1YmxpYy9idW5kbGVzL2FkZHJlc3NpbmcvanMvY291bnRyeUNvZGVDaGFuZ2UnO1xuIiwiLy8gZXh0cmFjdGVkIGJ5IG1pbmktY3NzLWV4dHJhY3QtcGx1Z2luXG5leHBvcnQge307IiwibW9kdWxlLmV4cG9ydHMgPSB7XG4gICAgLy8gVGhlIGFkZHJlc3MgZm9ybSBmaWVsZHMgYXJlIGluaXRpYWxpemVkIGhlcmUsIHRoZXNlIGZvcm0gZmllbGRzIGFyZSBjb21tb24gZm9yIHNvbWUgZW50aXR5IGZvcm1zIGFuZCBhcmUgdGhlcmVmb3JlIHB1dCBpbiB0aGlzIGZ1bmN0aW9uXG4gICAgaW5pdGlhbGl6ZTogZnVuY3Rpb24gKCkge1xuICAgICAgICAkKGRvY3VtZW50KS5yZWFkeShmdW5jdGlvbiAoKSB7XG4gICAgICAgICAgICAkKCcuYWRkcmVzcy1lbWJlZGRhYmxlJykub25jZSgnaW5pdGlhdGUtY291bnRyeS1jb2RlLWNoYW5nZScpLmVhY2goZnVuY3Rpb24gKCkge1xuICAgICAgICAgICAgICAgIHZhciBpZCA9ICQodGhpcykuYXR0cignaWQnKTtcblxuICAgICAgICAgICAgICAgIC8vIFdoZW4gdGhlIGNvdW50cnkgY29kZSBjaGFuZ2VzIHRoZSBmb3JtIHdpbGwgYmUgc3VibWl0dGVkIHNvIHdlIGdldCBhIHZhbGlkYXRlZCBmb3JtIGZvciB0aGF0IGNvdW50cnlcbiAgICAgICAgICAgICAgICAvLyBPbmx5IHRoZSBhZGRyZXNzIHBhcnQgaGVyZSBpcyBpbXBvcnRhbnQgdG8gY2hhbmdlIGluIHRoZSBjdXJyZW50IHZpZXdcbiAgICAgICAgICAgICAgICB2YXIgb25Db3VudHJ5Q29kZUNoYW5nZSA9IGZ1bmN0aW9uICgpIHtcbiAgICAgICAgICAgICAgICAgICAgdmFyICRmb3JtID0gJCh0aGlzKS5jbG9zZXN0KCdmb3JtJyk7XG4gICAgICAgICAgICAgICAgICAgIHZhciAkY291bnRyeUNvZGUgPSAkKHRoaXMpO1xuICAgICAgICAgICAgICAgICAgICB2YXIgJGFkZHJlc3MgPSAkY291bnRyeUNvZGUuY2xvc2VzdCgnLmFkZHJlc3MtZW1iZWRkYWJsZScpO1xuXG4gICAgICAgICAgICAgICAgICAgIHZhciBkYXRhID0ge307XG4gICAgICAgICAgICAgICAgICAgICRhZGRyZXNzRWxlbWVudHMgPSAkYWRkcmVzcy5maW5kKCcuZm9ybS1jb250cm9sJyk7XG4gICAgICAgICAgICAgICAgICAgICRhZGRyZXNzRWxlbWVudHMuZWFjaChmdW5jdGlvbiAoaW5kZXgsIGVsZW1lbnQpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGFbJChlbGVtZW50KS5hdHRyKCduYW1lJyldID0gJChlbGVtZW50KS52YWwoKTtcbiAgICAgICAgICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgICAgICAgICAgJC5hamF4KHtcbiAgICAgICAgICAgICAgICAgICAgICAgIHVybDogJGZvcm0uYXR0cignYWN0aW9uJyksXG4gICAgICAgICAgICAgICAgICAgICAgICB0eXBlOiAkZm9ybS5hdHRyKCdtZXRob2QnKSxcbiAgICAgICAgICAgICAgICAgICAgICAgIGRhdGE6IGRhdGEsXG4gICAgICAgICAgICAgICAgICAgICAgICBzdWNjZXNzOiBmdW5jdGlvbiAoaHRtbCkge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICRhZGRyZXNzLnJlcGxhY2VXaXRoKFxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAkKGh0bWwpLmZpbmQoJyMnICsgaWQpXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICB2YXIgJGNvdW50cnlDb2RlID0gJGZvcm0uZmluZCgnIycgKyBpZCArICdfY291bnRyeUNvZGUnKTtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkY291bnRyeUNvZGUuY2hhbmdlKG9uQ291bnRyeUNvZGVDaGFuZ2UpO1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICRjb3VudHJ5Q29kZS5jbG9zZXN0KCcuYWRkcmVzcy1lbWJlZGRhYmxlJykudHJpZ2dlcignY291bnRyeUNvZGVDaGFuZ2VkJyk7XG4gICAgICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgICAgIH0pO1xuICAgICAgICAgICAgICAgIH07XG5cbiAgICAgICAgICAgICAgICAkY291bnRyeUNvZGUgPSAkKCcjJyArIGlkICsgJ19jb3VudHJ5Q29kZScpO1xuICAgICAgICAgICAgICAgICRjb3VudHJ5Q29kZS5jaGFuZ2Uob25Db3VudHJ5Q29kZUNoYW5nZSk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSk7XG4gICAgfVxufTtcbiJdLCJuYW1lcyI6WyJtb2R1bGUiLCJleHBvcnRzIiwiaW5pdGlhbGl6ZSIsIiQiLCJkb2N1bWVudCIsInJlYWR5Iiwib25jZSIsImVhY2giLCJpZCIsImF0dHIiLCJvbkNvdW50cnlDb2RlQ2hhbmdlIiwiJGZvcm0iLCJjbG9zZXN0IiwiJGNvdW50cnlDb2RlIiwiJGFkZHJlc3MiLCJkYXRhIiwiJGFkZHJlc3NFbGVtZW50cyIsImZpbmQiLCJpbmRleCIsImVsZW1lbnQiLCJ2YWwiLCJhamF4IiwidXJsIiwidHlwZSIsInN1Y2Nlc3MiLCJodG1sIiwicmVwbGFjZVdpdGgiLCJjaGFuZ2UiLCJ0cmlnZ2VyIl0sInNvdXJjZVJvb3QiOiIifQ==