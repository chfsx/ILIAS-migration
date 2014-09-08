<?php
/**
 * Class iQuestionCondition
 *
 * Date: 02.12.13
 * Time: 14:02
 * @author Thomas Joußen <tjoussen@databay.de>
 */
interface iQuestionCondition {

	const StringResultExpression = '~TEXT~';
	const PercentageResultExpression = '%n%';
	const NumericResultExpression = '#n#';
	const MatchingResultExpression = ';n:m;';
	const OrderingResultExpression = '$n,m,o,p$';
	const NumberOfResultExpression = '+n+';

	/**
	 * Get all available operations for a specific question
	 *
	 * @param $expression
	 *
	 * @internal param string $expression_type
	 * @return array
	 */
	public function getOperators($expression);

	/**
	 * Get all available expression types for a specific question
	 *
	 * @return array
	 */
	public function getExpressionTypes();

	/**
	 * Get the user solution for a question by active_id and the test pass
	 *
	 * @param int $active_id
	 * @param int $pass
	 *
	 * @return ilUserQuestionResult
	 */
	public function getUserQuestionResult($active_id, $pass);
} 