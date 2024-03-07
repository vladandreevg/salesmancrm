function dotify(address) {

	var shortTypes = ['аобл', 'респ', 'вл', 'г', 'д', 'двлд', 'днп', 'дор', 'дп', 'жт', 'им', 'к', 'кв', 'км', 'комн', 'кп', 'лпх', 'м',  'мкр', 'наб', 'нп', 'обл', 'оф', 'п', 'пгт', 'пер', 'пл', 'платф', 'рзд', 'рп', 'с', 'сл', 'снт', 'ст', 'стр', 'тер', 'туп', 'ул', 'х', 'ш'];
	var words = address.split(" ");
	var dottedWords = words.map(function(word) {

		if (shortTypes.indexOf(word) !== -1) {
			return word + '.';
		}
		else {
			return word;
		}

	});

	return dottedWords.join(" ");

}

function formatResult(value) {

	return dotify(value);

}

function formatSelected(suggestion) {

	return dotify(suggestion.value);

}