exports.fixTagsFormat = (fileContent) => {
  // fix tags array
  let result = fileContent.replace(/"tags":("\[.*?\]"),/gm, (substring, captureGroup) => {
    const value = captureGroup
      .replace('"[', '[')
      .replace(']"', ']')
      .replace(/\\\"/g,'"');
    // console.log(captureGroup, value);
    return `"tags":${value},`;
  });

  // strip out "test" assign_to since it's not an integer
  result = result.replace(/"assigned_to":\[(.*?)\],/gm, (substring, captureGroup) => {
    let value = '';
    if (captureGroup.length) {
      value = captureGroup
        .split(',')
        .filter(x => x !== '"test"' && x !== 'undefined')
        .join(',')
    }
    return `"assigned_to":[${value}],`;
  });
  return result;
};
