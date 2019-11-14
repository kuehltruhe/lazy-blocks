import arrayMove from 'array-move';
import classnames from 'classnames/dedupe';

import RepeaterControl from './repeater-control';

const { __, _n, sprintf } = wp.i18n;

const {
    Fragment,
    Component,
} = wp.element;

const {
    PanelBody,
    BaseControl,
    TextControl,
    CheckboxControl,
} = wp.components;

const {
    addFilter,
} = wp.hooks;

/**
 * Control render in editor.
 */
addFilter( 'lzb.editor.control.repeater.render', 'lzb.editor', ( render, props ) => {
    const val = props.getValue();

    return (
        <RepeaterControl
            controlData={ props.data }
            label={ props.data.label }
            count={ val.length }
            getInnerControls={ ( index ) => {
                const innerControls = props.getControls( props.uniqueId );
                const innerResult = {};

                Object.keys( innerControls ).forEach( ( i ) => {
                    const innerData = innerControls[ i ];

                    innerResult[ i ] = {
                        data: innerData,
                        val: props.getValue( innerData, index ),
                    };
                } );

                return innerResult;
            } }
            renderRow={ ( index ) => (
                <Fragment>
                    { props.renderControls( props.placement, props.uniqueId, index ) }
                </Fragment>
            ) }
            removeRow={ ( i ) => {
                if ( i > -1 ) {
                    val.splice( i, 1 );
                    props.onChange( val );
                }
            } }
            addRow={ () => {
                val.push( {} );
                props.onChange( val );
            } }
            resortRow={ ( oldIndex, newIndex ) => {
                const newVal = arrayMove( val, oldIndex, newIndex );
                props.onChange( newVal );
            } }
        />
    );
} );

/**
 * getValue filter in editor.
 */
addFilter( 'lzb.editor.control.repeater.getValue', 'lzb.editor', ( value ) => {
    // change string value to array.
    if ( typeof value === 'string' ) {
        try {
            value = JSON.parse( decodeURI( value ) );
        } catch ( e ) {
            value = [];
        }
    }

    return value;
} );

/**
 * updateValue filter in editor.
 */
addFilter( 'lzb.editor.control.repeater.updateValue', 'lzb.editor', ( value ) => {
    // change array value to string.
    if ( typeof value === 'object' || Array.isArray( value ) ) {
        value = encodeURI( JSON.stringify( value ) );
    }

    return value;
} );

/**
 * Repeater item with childs render
 */
class ControlsRepeaterItem extends Component {
    constructor() {
        super( ...arguments );

        this.state = {
            collapsedChilds: false,
        };

        this.toggleCollapseChilds = this.toggleCollapseChilds.bind( this );
    }

    toggleCollapseChilds() {
        this.setState( { collapsedChilds: ! this.state.collapsedChilds } );
    }

    render() {
        const {
            printControls,
            id,
            controls,
        } = this.props;

        const {
            collapsedChilds,
        } = this.state;

        // repeater child items count.
        let childItemsNum = 0;
        Object.keys( controls ).forEach( ( thisId ) => {
            const controlData = controls[ thisId ];

            if ( controlData.child_of === id ) {
                childItemsNum++;
            }
        } );

        let toggleText = __( 'Show Child Controls' );
        if ( collapsedChilds ) {
            toggleText = __( 'Hide Child Controls' );
        } else if ( childItemsNum ) {
            toggleText = sprintf( _n( 'Show %d Child Control', 'Show %d Child Controls', childItemsNum ), childItemsNum );
        }

        return (
            <Fragment>
                <button
                    className={ classnames( 'lzb-constructor-controls-item-repeater-toggle', collapsedChilds ? 'lzb-constructor-controls-item-repeater-toggle-collapsed' : '' ) }
                    onClick={ ( e ) => {
                        e.stopPropagation();
                        e.preventDefault();
                        this.toggleCollapseChilds();
                    } }
                >
                    { toggleText }
                    <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
                        <path fill="currentColor" d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z" />
                    </svg>
                </button>
                { collapsedChilds ? (
                    <div className="lzb-constructor-controls-item-childs">
                        { printControls( id ) }
                    </div>
                ) : '' }
            </Fragment>
        );
    }
}

/**
 * Control lists item render in constructor.
 */
addFilter( 'lzb.constructor.controls.repeater.item', 'lzb.constructor', ( render, props ) => {
    return (
        <Fragment>
            { render }
            <ControlsRepeaterItem { ...props } />
        </Fragment>
    );
} );

/**
 * Control settings render in constructor.
 */
addFilter( 'lzb.constructor.control.repeater.settings', 'lzb.constructor', ( render, props ) => {
    const {
        updateData,
        data,
    } = props;

    return (
        <Fragment>
            <PanelBody>
                <TextControl
                    label={ __( 'Row Label' ) }
                    placeholder={ __( 'Row {{#}}' ) }
                    help={ __( 'Example: "My row number {{#}} with inner control {{control_name}}"' ) }
                    value={ data.rows_label }
                    onChange={ ( value ) => updateData( { rows_label: value } ) }
                />
            </PanelBody>
            <PanelBody>
                <TextControl
                    label={ __( 'Add Button Label' ) }
                    placeholder={ __( '+ Add Row' ) }
                    value={ data.rows_add_button_label }
                    onChange={ ( value ) => updateData( { rows_add_button_label: value } ) }
                />
            </PanelBody>
            <PanelBody>
                <TextControl
                    type="number"
                    label={ __( 'Minimum Rows' ) }
                    placeholder={ 0 }
                    min={ 0 }
                    value={ data.rows_min }
                    onChange={ ( value ) => updateData( { rows_min: value } ) }
                />
            </PanelBody>
            <PanelBody>
                <TextControl
                    type="number"
                    label={ __( 'Maximum Rows' ) }
                    placeholder={ 0 }
                    min={ 0 }
                    value={ data.rows_max }
                    onChange={ ( value ) => updateData( { rows_max: value } ) }
                />
            </PanelBody>
            <PanelBody>
                <BaseControl
                    label={ __( 'Collapsible Rows' ) }
                >
                    <CheckboxControl
                        label={ __( 'Yes' ) }
                        checked={ 'true' === data.rows_collapsible }
                        onChange={ ( value ) => updateData( { rows_collapsible: value ? 'true' : 'false' } ) }
                    />
                    { 'true' === data.rows_collapsible ? (
                        <CheckboxControl
                            label={ __( 'Collapsed by Default' ) }
                            checked={ 'true' === data.rows_collapsed }
                            onChange={ ( value ) => updateData( { rows_collapsed: value ? 'true' : 'false' } ) }
                        />
                    ) : '' }
                </BaseControl>
            </PanelBody>
        </Fragment>
    );
} );
