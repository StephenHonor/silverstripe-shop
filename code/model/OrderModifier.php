<?php
/**
 * The OrderModifier class is a databound object for
 * handling the additional charges or deductions of
 * an order.
 *
 * @package shop
 * @subpackage modifiers
 */
class OrderModifier extends OrderAttribute {

	public static $db = array(
		'Amount' => 'Currency',
		'Type' => "Enum('Chargable,Deductable,Ignored','Chargable')", //TODO: deperecate this in a future release
		'Sort' => 'Int'
	);
	
	public static $defaults = array(
		'Type' => 'Chargable'
	);

	public static $casting = array(
		'TableValue' => 'Currency',
		'CartValue' => 'Currency'
	);

	public static $searchable_fields = array(
		'OrderID' => array(
			'title' => 'Order ID',
			'field' => 'TextField'
		),
		"Title" => "PartialMatchFilter",
		"TableTitle" => "PartialMatchFilter",
		"CartTitle" => "PartialMatchFilter",
		"Amount",
		"Type"
	);

	public static $field_labels = array();
	public static $summary_fields = array(
		"Order.ID" => "Order ID",
		"TableTitle" => "Table Title",
		"ClassName" => "Type",
		"Amount" => "Amount" ,
		"Type" => "Type"
	);

	public static $singular_name = "Modifier";
	function i18n_singular_name() {	return _t("OrderModifier.SINGULAR", self::$singular_name); }
	public static $plural_name = "Modifiers";
	function i18n_plural_name() { return _t("OrderModifier.PLURAL", self::$plural_name); }

	public static $default_sort = "\"Sort\" ASC, \"Created\" ASC";
	
	/**
	 * @deprecated
	 */
	protected static $is_chargable = true;
	
	/**
	* Specifies whether this modifier is always required in an order.
	*/
	public function required(){
		return true;
	}
	
	/**
	 * Modifies the incoming value by adding, subtracting or ignoring the value this modifier calculates.
	 */
	public function modify($incoming){
		switch($this->Type){
			case "Chargable":
				$incoming += $this->value($incoming);
				break;
			case "Deductable":
				$incoming -= $this->value($incoming);
				break;
			case "Ignored":
				$this->value($incoming); //needs to be called to store Amount
				break;
		}
		return $incoming;
	}
	
	/**
	 * Calculates value to store, based on incoming running total.
	 * @param float $incoming the incoming running total.
	 */
	public function value($incoming){
		return $this->Amount = 0;
	}

	/**
	 * This function is always called to determine the
	 * amount this modifier needs to charge or deduct.
	 *
	 * If the modifier exists in the DB, in which case it
	 * already exists for a given order, we just return
	 * the Amount data field from the DB. This is for
	 * existing orders.
	 *
	 * If this is a new order, and the modifier doesn't
	 * exist in the DB ($this->ID is 0), so we return
	 * the amount from $this->LiveAmount() which is a
	 * calculation based on the order and it's items.
	 */
	function Amount() {
		return $this->Amount;
	}
	
	/**
	 * Monetary to use in templates.
	 */
	function TableValue() {
		return $this->Total();
	}
	
	/**
	* Produces a title for use in templates.
	* @return string
	*/
	function Title(){
		return $this->i18n_singular_name();
	}

	/**
	* Provides a modifier total that is positive or negative, depending on whether the modifier is chargable or not.
	*
	* @return boolean
	*/
	function Total() {
		if($this->Type == "Deductable"){
			return $this->Amount * -1;
		}
		return $this->Amount;
	}
	
	/**
	 * If the current instance of this OrderModifier
	 * exists in the database, check if the Type in
	 * the DB field is "Chargable", if it is, return
	 * true, otherwise check the static "is_chargable",
	 * since this instance currently isn't in the DB.
	 *
	 * @return boolean
	 */
	function IsChargable() {
		return $this->Type == "Chargable";
	}

	/**
	 * Checks if the modifier can be removed.
	 * Default check is for whether it is chargable.
	 *
	 * @return boolean
	 */
	function CanRemove() {
		return !$this->stat('is_chargable');
	}

	function removeLink() {
		return ShoppingCart::remove_modifier_link($this->ID);
	}

	/**
	 * Debug helper method.
	 */
	public function debug() {
		$id = $this->ID;
		$amount = $this->Amount();
		$type = $this->IsChargable() ? 'Chargable' : 'Deductable';
		$orderID = $this->ID ? $this->OrderID : 'The order has not been saved yet, so there is no ID';
		return <<<HTML
			<h2>$this->class</h2>
			<h3>OrderModifier class details</h3>
			<p>
				<b>ID : </b>$id<br/>
				<b>Amount : </b>$amount<br/>
				<b>Type : </b>$type<br/>
				<b>Order ID : </b>$orderID
			</p>
HTML;
	}
	
	//deprecated functions

	/**
	* @deprecated use Title
	*/
	function TableTitle(){
		return $this->Title();
	}
	
	/**
	 * @deprecated use Title
	 */
	function CartValue() {
		return $this->Title();
	}
	
	/**
	 * @deprecated use Amount
	 */
	protected function LiveAmount() {
		return $this->value();
	}

	//TODO: remove these functions
	
	/**
	* This determines whether the OrderModifierForm
	* is shown or not. {@link OrderModifier::get_form()}.
	*
	* @return boolean
	*/
	static function show_form() {
		return false;
	}
	
	/**
	 * This function returns a form that allows a user
	 * to change the modifier to the order.
	 *
	 * @todo When is this used?
	 * @todo How is this used?
	 * @todo How does one create their own OrderModifierForm implementation?
	 *
	 * @param Controller $controller $controller The controller
	 * @return OrderModifierForm or subclass
	 */
	static function get_form($controller) {
		return new OrderModifierForm($controller, 'ModifierForm', new FieldSet(), new FieldSet());
	}
	
	function updateForAjax(array &$js) {
		$amount = $this->obj('Amount')->Nice();
		$js[] = array('id' => $this->CartTotalID(), 'parameter' => 'innerHTML', 'value' => $amount);
		$js[] = array('id' => $this->TableTotalID(), 'parameter' => 'innerHTML', 'value' => $amount);
		$js[] = array('id' => $this->TableTitleID(), 'parameter' => 'innerHTML', 'value' => $this->TableTitle());
	}
	
}