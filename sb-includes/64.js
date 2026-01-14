/**
*
*  Base64 encode / decode
*  http://www.webtoolkit.info/
*
**/

const Base64 = {

	// private property
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

	// public method for encoding
	encode : function (input) {
		let output = "";
		let chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		let i = 0;

		input = Base64._utf8_encode(input);

		while (i < input.length) {

			chr1 = input.codePointAt(i++);
			chr2 = input.codePointAt(i++);
			chr3 = input.codePointAt(i++);

			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;

			if (Number.isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (Number.isNaN(chr3)) {
				enc4 = 64;
			}

			output = output +
			this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
			this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

		}

		return output;
	},

	// public method for decoding
	decode : function (input) {
		let output = "";
		let chr1, chr2, chr3;
		let enc1, enc2, enc3, enc4;
		let i = 0;

		input = input.replaceAll(/[^A-Za-z0-9+/=]/g, "");

		while (i < input.length) {

			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));

			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;

			output = output + String.fromCodePoint(chr1);

			if (enc3 != 64) {
				output = output + String.fromCodePoint(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCodePoint(chr3);
			}

		}

		output = Base64._utf8_decode(output);

		return output;

	},

	// private method for UTF-8 encoding
	_utf8_encode : function (string) {
		string = string.replaceAll("\r\n", "\n");
		let utftext = "";

		for (let n = 0; n < string.length; n++) {

			const c = string.codePointAt(n);

			if (c < 128) {
				utftext += String.fromCodePoint(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCodePoint((c >> 6) | 192);
				utftext += String.fromCodePoint((c & 63) | 128);
			}
			else {
				utftext += String.fromCodePoint((c >> 12) | 224);
				utftext += String.fromCodePoint(((c >> 6) & 63) | 128);
				utftext += String.fromCodePoint((c & 63) | 128);
			}

		}

		return utftext;
	},

	// private method for UTF-8 decoding
	_utf8_decode : function (utftext) {
		let string = "";
		let i = 0;
		let c = 0, c2 = 0, c3 = 0;

		while ( i < utftext.length ) {

			c = utftext.codePointAt(i);

			if (c < 128) {
				string += String.fromCodePoint(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.codePointAt(i+1);
				string += String.fromCodePoint(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.codePointAt(i+1);
				c3 = utftext.codePointAt(i+2);
				string += String.fromCodePoint(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}

		}

		return string;
	}

}