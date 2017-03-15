<?php
/**
 * TP24_Sniffs_NamingConventions_ValidFunctionNameSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   CVS: $Id: ValidFunctionNameSniff.php,v 1.18 2008/01/22 23:50:23 squiz Exp $
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

if (class_exists('PHP_CodeSniffer_Standards_AbstractScopeSniff', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_AbstractScopeSniff not found');
}

/**
 * TP24_Sniffs_NamingConventions_ValidFunctionNameSniff.
 *
 * Ensures method names are correct depending on whether they are public
 * or private, and that functions are named correctly.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   http://matrix.squiz.net/developer/tools/php_cs/licence BSD Licence
 * @version   Release: 1.2.0RC1
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class TP24_Sniffs_NamingConventions_ValidFunctionNameSniff extends PHP_CodeSniffer_Standards_AbstractScopeSniff
{

    /**
     * A list of all PHP magic methods.
     *
     * @var array
     */
    private $_magicMethods = array(
                              'construct',
							  'invoke',
                              'destruct',
                              'call',
                              'callStatic',
                              'get',
                              'set',
                              'isset',
                              'unset',
                              'sleep',
                              'wakeup',
                              'toString',
                              'set_state',
                              'clone',
                             );

    /**
     * A list of all PHP magic functions.
     *
     * @var array
     */
    private $_magicFunctions = array(
		'autoload'
	);

	private $_allowPublic = array(
		'_doRequest', '__doRequest' // customization for TP24_Soap_Client_Adapter_Http extending SoapClient
		);
	private $_allowProtected = array(
		'init', 'preInsert', 'postInsert', 'preUpdate', 'postUpdate', 'preSave', 'postSave'  // customization for TP24_Db_Mongo_Document_AbstractTest extending Shanty_Mongo_Document
		);

    /**
     * Constructs a TP24_Sniffs_NamingConventions_ValidFunctionNameSniff.
     */
    public function __construct()
    {
        parent::__construct(array(T_CLASS, T_INTERFACE, T_TRAIT), array(T_FUNCTION), true);

    }//end __construct()


    /**
     * Processes the tokens within the scope.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
     * @param int                  $stackPtr  The position where this token was
     *                                        found.
     * @param int                  $currScope The position of the current scope.
     *
     * @return void
     */
    protected function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope)
    {
        $className  = $phpcsFile->getDeclarationName($currScope);
        $methodName = $phpcsFile->getDeclarationName($stackPtr);

        // Is this a magic method. IE. is prefixed with "__".
        if (preg_match('|^__|', $methodName) !== 0) {
            $magicPart = substr($methodName, 2);
            if (in_array($magicPart, $this->_magicMethods) === false && !(in_array($methodName, $this->_allowPublic) === true)) {
                 $error = "Method name \"$className::$methodName\" is invalid; only PHP magic methods should be prefixed with a double underscore";
                 $phpcsFile->addError($error, $stackPtr);
            }

            return;
        }

        // PHP4 constructors are allowed to break our rules.
        if ($methodName === $className) {
            return;
        }


        // PHP4 destructors are allowed to break our rules.
        if ($methodName === '_'.$className) {
            return;
        }

        $methodProps    = $phpcsFile->getMethodProperties($stackPtr);
        $isPublic       = ($methodProps['scope'] !== 'private' && $methodProps['scope'] !== 'protected') ? true : false;
        $isProtected    = ($methodProps['scope'] === 'protected') ? true : false;
        $isPrivate      = ($methodProps['scope'] === 'private') ? true : false;
        $scope          = $methodProps['scope'];

        // If it's a public method, it must not have an underscore on the front. Unless contained on _allowPublic array
        if ($isPublic === true && $methodName{0} === '_' && !(in_array($methodName, $this->_allowPublic) === true)) {
            $error = ucfirst($scope)." method name \"$className::$methodName\" must not be prefixed with an underscore";
            $phpcsFile->addError($error, $stackPtr);
            return;
        }

        // If it's a protected method, it must have an underscore on the front.
        if ($isProtected === true && $methodName{0} !== '_' && !(in_array($methodName, $this->_allowProtected) === true)) {
		echo $methodName.PHP_EOL;
            $error = "Protected method name \"$className::$methodName\" must be prefixed with an underscore";
            $phpcsFile->addError($error, $stackPtr);
            return;
        }

        // If it's a private method, it must have an underscore on the front.
        if ($isPrivate === true && $methodName{0} !== '_') {
            $error = "Private method name \"$className::$methodName\" must be prefixed with an underscore";
            $phpcsFile->addError($error, $stackPtr);
            return;
        }

		//check for camelCaps Unless contained on _allowPublic
        if (PHP_CodeSniffer::isCamelCaps($methodName, false, $isPublic, false) === false &&
			!(in_array($methodName, $this->_allowPublic) === true) &&
			!(in_array($methodName, $this->_allowProtected) === true)) {
            $error = ucfirst($scope)." method name \"$className::$methodName\" is not in camel caps format";
            $phpcsFile->addError($error, $stackPtr);
            return;
        }

    }//end processTokenWithinScope()


    /**
     * Processes the tokens outside the scope.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being processed.
     * @param int                  $stackPtr  The position where this token was
     *                                        found.
     *
     * @return void
     */
    protected function processTokenOutsideScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $functionName = $phpcsFile->getDeclarationName($stackPtr);

        // Is this a magic function. IE. is prefixed with "__".
        if (preg_match('|^__|', $functionName) !== 0) {
            $magicPart = substr($functionName, 2);
            if (in_array($magicPart, $this->_magicFunctions) === false) {
                 $error = "Function name \"$functionName\" is invalid; only PHP magic methods should be prefixed with a double underscore";
                 $phpcsFile->addError($error, $stackPtr);
            }

            return;
        }

        // Function names can be in two parts; the package name and
        // the function name.
        $packagePart   = '';
        $camelCapsPart = '';
        $underscorePos = strrpos($functionName, '_');
        if ($underscorePos === false) {
            $camelCapsPart = $functionName;
        } else {
            $packagePart   = substr($functionName, 0, $underscorePos);
            $camelCapsPart = substr($functionName, ($underscorePos + 1));

            // We don't care about _'s on the front.
            $packagePart = ltrim($packagePart, '_');
        }

        // If it has a package part, make sure the first letter is a capital.
        if ($packagePart !== '') {
            if ($functionName{0} === '_') {
                $error = "Function name \"$functionName\" is invalid; only private methods should be prefixed with an underscore";
                $phpcsFile->addError($error, $stackPtr);
                return;
            }

            if ($functionName{0} !== strtoupper($functionName{0})) {
                $error = "Function name \"$functionName\" is prefixed with a package name but does not begin with a capital letter";
                $phpcsFile->addError($error, $stackPtr);
                return;
            }
        }

        // If it doesn't have a camel caps part, it's not valid.
        if (trim($camelCapsPart) === '') {
            $error = "Function name \"$functionName\" is not valid; name appears incomplete";
            $phpcsFile->addError($error, $stackPtr);
            return;
        }

        $validName        = true;
        $newPackagePart   = $packagePart;
        $newCamelCapsPart = $camelCapsPart;

        // Every function must have a camel caps part, so check that first.
        if (PHP_CodeSniffer::isCamelCaps($camelCapsPart, false, true, false) === false) {
            $validName        = false;
            $newCamelCapsPart = strtolower($camelCapsPart{0}).substr($camelCapsPart, 1);
        }

        if ($packagePart !== '') {
            // Check that each new word starts with a capital.
            $nameBits = explode('_', $packagePart);
            foreach ($nameBits as $bit) {
                if ($bit{0} !== strtoupper($bit{0})) {
                    $newPackagePart = '';
                    foreach ($nameBits as $bit) {
                        $newPackagePart .= strtoupper($bit{0}).substr($bit, 1).'_';
                    }

                    $validName = false;
                    break;
                }
            }
        }

        if ($validName === false) {
            $newName = rtrim($newPackagePart, '_').'_'.$newCamelCapsPart;
            if ($newPackagePart === '') {
                $newName = $newCamelCapsPart;
            } else {
                $newName = rtrim($newPackagePart, '_').'_'.$newCamelCapsPart;
            }

            $error = "Function name \"$functionName\" is invalid; consider \"$newName\" instead";
            $phpcsFile->addError($error, $stackPtr);
        }

    }//end processTokenOutsideScope()


}//end class

?>