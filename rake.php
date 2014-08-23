<?php

class RAKE {

	private $stoplist = [];
	/*
		Takes a string with delimeter or an array as a stoplist
	 */
	public function __construct( $stoplist, $delim = ',' ) {
		if ( is_array( $stoplist ) ) {
			$this->stoplist = $stoplist;
		} else if ( is_string( $stoplist ) ) {
			$this->stoplist = explode( $delim, $stoplist );
		}
	}

	private function split_into_sentences( $text ) {
		$pattern =  '/[.!?,;:\t\-\"\(\)\']/'; 
		return preg_split( $pattern, $text );
	}

	private function split_into_words( $phrase, $min_chars = 0 ) {
		$word_list = [];
		$pattern = '/\P{L}+/u';

		$words = preg_split( $pattern, $phrase );

		foreach( $words as $word ) {
			$word = trim( $word );

			if ( strlen( $word ) > $min_chars && $word != "" && ! is_numeric( $word ) ) {
				array_push( $word_list, $word );
			}
		}

		return $word_list;
	}
	/* Build a regular expression containing our stopwords */
	private function get_stopword_regex() {
		$words_regex = implode( '|', $this->stoplist );
		return '/\b(' . trim( $words_regex ) . ')\b/i'; 
	}
	/* Get phrases from our text using stopwords regex */
	private function get_phrases( $sentence_list ) {
		$phrase_list = [];
		$stopword_pattern = $this->get_stopword_regex();

		foreach( $sentence_list as $sentence ) {
			$tmp = preg_replace( $stopword_pattern, '|', $sentence );
			$phrases = explode( '|', $tmp );
			foreach( $phrases as $phrase ) {
				$phrase = strtolower( trim( $phrase ) );
				if ( $phrase != "" ) {
					array_push( $phrase_list , $phrase );
				}
			}
		}

		return $phrase_list;
	}
	/* Get scores for individual words depending on their frequency,
	 * degree and ratio of degree/freqeuncy
	 */
	private function get_word_scores( $phrase_list ) {
		$word_frq = [];
		$word_degree = [];
		foreach( $phrase_list as $phrase ) {
			$word_list = $this->split_into_words( $phrase ); 
			$word_list_length = count( $word_list );
			$word_list_degree = $word_list_length - 1;
			foreach ( $word_list as $word ) {
				if ( array_key_exists( $word, $word_frq) ) {
					$word_frq[ $word ] += 1;
				} else {
					$word_frq[ $word ] = 1;
				}
				if ( array_key_exists( $word, $word_degree) ) {
					$word_degree[ $word ] += $word_list_degree;
				} else {
					$word_degree[ $word ] = $word_list_degree;
				}
			}
		}
		foreach ( $word_frq as $item => $value ) {
			$word_degree[ $item ] = 
				$word_degree[ $item ] + $word_frq[ $item ];
		}
		$word_score = [];
		foreach ( $word_frq as $item => $value ) {
			$word_score[ $item ] = round(
				floatval( $word_degree[ $item ] ) / floatval( $word_frq[ $item ] )
		        	,2 );
		}

		return $word_score;
	}

	public function get_phrase_scores( $phrase_list, $word_scores ) {
		$phrase_scores = [];

		foreach ( $phrase_list as $phrase ) {
			if ( ! array_key_exists( $phrase, $phrase_scores ) ) {
				$phrase_scores[ $phrase ] = 0;
			}
			$word_list = $this->split_into_words( $phrase );
			$total_score = 0;
			foreach ( $word_list as $word ) {
				$total_score += $word_scores[ $word ];
			}
			$phrase_scores[ $phrase ] = $total_score;
		}

		return $phrase_scores;	
	}

	public function extract( $text ) {
		$sentence_list = $this->split_into_sentences( $text );

		$phrase_list = $this->get_phrases( $sentence_list );

		$word_scores = $this->get_word_scores( $phrase_list );

		$candidates = $this->get_phrase_scores( $phrase_list, $word_scores );

		return $candidates;
	}

}

//get stoplist
$stoplist = [];
$handle = fopen("SmartStoplist.txt", "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
	    if ( strpos( $line, '#' ) === 0 ) {
	    	continue;
	    } else {
	    	array_push( $stoplist, trim( $line ) );	
	    }
    }
} else {
	echo 'Error opening file';
	return;
} 
fclose($handle);

$rake = new RAKE( $stoplist );

$test_string = "Compatibility of systems of linear constraints over the set of natural numbers. Criteria of compatibility of a system of linear Diophantine equations, strict inequations, and nonstrict inequations are considered. Upper bounds for components of a minimal set of solutions and algorithms of construction of minimal generating sets of solutions for all types of systems are given. These criteria and the corresponding algorithms for constructing a minimal supporting set of solutions can be used in solving all the considered types of systems and systems of mixed types.";

$candidates = $rake->extract( $test_string );

arsort( $candidates );

var_dump( $candidates );

?>
