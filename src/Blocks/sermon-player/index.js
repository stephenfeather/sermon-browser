/**
 * Sermon Player Block
 *
 * Registers the sermon-browser/sermon-player block.
 *
 * @package sermon-browser
 */

import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: Edit,
	// No save function - server-side rendered
} );
