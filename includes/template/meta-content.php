<?php
if ( isset( $_POST['version'] ) ) {
  $post_data = $_POST['version'];
}

?>


<tr>
	<td class="sort">&nbsp;</td>
	<td><input type="number" step="any" min="0" value="' . esc_attr( $rate['minO'] ) . '" name="' . esc_attr( $this->id .'_minO[' . $i . ']' ) . '" style="width: 90%" class="' . esc_attr( $this->id .'field[' . $i . ']' ) . '" placeholder="'.__( '0.00', 'rptr' ).'" size="4" /></td>
	<td><input type="number" step="any" min="0" value="' . esc_attr( $rate['maxO'] ) . '" name="' . esc_attr( $this->id .'_maxO[' . $i . ']' ) . '" style="width: 90%" class="' . esc_attr( $this->id .'field[' . $i . ']' ) . '" placeholder="'.__( '0.00', 'rptr' ).'" size="4" /></td>
	<td><input type="number" step="any" min="0" value="' . esc_attr( $rate['shippingO'] ) . '" name="' . esc_attr( $this->id .'_shippingO[' . $i . ']' ) . '" style="width: 90%" class="' . esc_attr( $this->id .'field[' . $i . ']' ) . '" placeholder="'.__( '0.00', 'rptr' ).'" size="4" /></td>
	<td class="remove">&nbsp;</td>
</tr>